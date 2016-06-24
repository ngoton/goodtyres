<?php
Class newquotationController Extends baseController {
    
    public function index(){

    }

    public function getTransport(){
        if(isset($_SERVER['HTTP_ORIGIN'])){
            switch ($_SERVER['HTTP_ORIGIN']) {
                case 'http://tancangmientrung.com': case 'https://tancangmientrung.com':
                header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
                header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
                header('Access-Control-Max-Age: 1000');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
                break;
            }
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $new_from = isset($_POST['new_from']) ? $_POST['new_from'] : null;
            $new_to = isset($_POST['new_to']) ? $_POST['new_to'] : null;
            $loc_from = isset($_POST['loc_from']) ? $_POST['loc_from'] : null;
            $loc_to = isset($_POST['loc_to']) ? $_POST['loc_to'] : null;
            $normal = isset($_POST['normal']) ? $_POST['normal'] : null;
            $special = isset($_POST['special']) ? $_POST['special'] : null;
            $tons = isset($_POST['tons']) ? $_POST['tons'] : 0;
            $soluong = isset($_POST['soluong']) ? $_POST['soluong'] : 0;
            $sotan = isset($_POST['sotan']) ? $_POST['sotan'] : 0;
            $fix = 1;
            //$opt = isset($_SESSION['userid_logined'])?0:100000;
            $opt = 0;


            $quatai = isset($_POST['quatai']) ? $_POST['quatai'] : null;

            $dist_from = isset($_POST['dist_from']) ? $_POST['dist_from'] : null;
            $dist_to = isset($_POST['dist_to']) ? $_POST['dist_to'] : null;

            $loaihang = isset($_POST['loaihang']) ? $_POST['loaihang'] : null;

            $customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : null;
            $customer_email = isset($_POST['customer_email']) ? $_POST['customer_email'] : null;
            $customer_phone = isset($_POST['customer_phone']) ? $_POST['customer_phone'] : null;

            $customer_model = $this->model->get('customerModel');

            if ($customer_name != null) {
                $cus_data = array(
                    'customer_name' => trim($customer_name),
                    'customer_email' => trim($customer_email),
                    'customer_phone' => trim($customer_phone),
                );
                if ($customer_model->getCustomerByWhere(array('customer_name'=>$cus_data['customer_name']))) {
                    $id_customer = $customer_model->getCustomerByWhere(array('customer_name'=>$cus_data['customer_name']))->customer_id;
                }
                else{
                    $customer_model->createCustomer($cus_data);
                    $id_customer = $customer_model->getLastCustomer()->customer_id;
                }
            }

            $transport_model = $this->model->get('newtransportModel');
            $e_customer = $this->model->get('ecustomerModel');
            $e_transport = $this->model->get('etransportModel');
            $location_model = $this->model->get('locationModel');

            if (isset($_COOKIE['cus_email'])) {
                $congty = $e_customer->getCustomerByWhere(array('e_customer_email'=>$_COOKIE['cus_email']));
            }
            else{
                $congty = (object) array('e_customer_id'=>null);
                $congty->e_customer_id = null;
            }

            


            if ($loc_from == '') {
                
                if (!$location_model->getLocationByWhere(array('location_name'=>trim($new_from)))) {
                  
                    $data_from = array(
                        'location_name' => trim($new_from),
                        'district' => $dist_from,
                        );
                    $location_model->createLocation($data_from);

                    $loc_from = $location_model->getLastLocation()->location_id;
                }
                else{
                    $loc_from = $location_model->getLocationByWhere(array('location_name'=>trim($new_from)))->location_id;
                }
            }
            if ($loc_to == '' ) {
                if (!$location_model->getLocationByWhere(array('location_name'=>trim($new_to)))) {
                    $data_to = array(
                        'location_name' => trim($new_to),
                        'district' => $dist_to,
                        );
                    $location_model->createLocation($data_to);

                    $loc_to = $location_model->getLastLocation()->location_id;
                }
                else{
                    $loc_to = $location_model->getLocationByWhere(array('location_name'=>trim($new_to)))->location_id;
                }


            }

           

                
            

            if ($loc_from != '' && $loc_to != '' ) {

                $j = array('table'=>'vendor','where'=>'new_transport.vendor = vendor.vendor_id');
                $dt = $transport_model->getAllTransport(array('where'=>'loc_from = '.$loc_from.' AND loc_to = '.$loc_to),$j);
                
                $trans_from = 0;
                $trans_to = 0;

                if ($loc_from == $loc_to) {
                    
                    $data = array(
                        'fix' => 0,
                        'bo' => 0,
                        'thuy' => 0,
                        'err' => null,
                        'from' => $loc_from,
                        'to' => $loc_to,
                        'data' => null,
                        );
                    echo json_encode($data);
                    //return false;
                }
                else if ($loc_from != $loc_to) {
                    $transports = $transport_model->getTransportByField('c20_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                    if ($transports == null) {
                        $transports = $transport_model->getTransportByField('c20_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                    }
                    if ($transports == null) {
                        $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                    }
                    if ($transports == null) {
                        $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                    }

                    if ($transports == null) {
                        
                        $e_transport_data = array(
                            'e_transport_date' => strtotime(date('d-m-Y')),
                            'e_loc_from' => $loc_from,
                            'e_loc_to' => $loc_to,
                            'customer' => $congty->e_customer_id,
                            'sale' => isset($_SESSION['userid_logined'])?$_SESSION['userid_logined']:null,
                        );


                        $e_transport->createTransport($e_transport_data);

                        if (isset($_COOKIE['cus_email'])) {
                               
                            $content_mail = array(
                                'congty'=> $congty->e_customer_co,
                                'email' => $congty->e_customer_email,
                                'sdt' => $congty->e_customer_phone,
                                'tuyenduong'=> $location_model->getLocationByWhere(array('location_id'=>trim($loc_from)))->location_name.' - '.$location_model->getLocationByWhere(array('location_id'=>trim($loc_to)))->location_name,
                                'feet'=> $normal>0?$normal.' feet':'40 feet',
                                'loai'=> $special==1?'DC':($special==2?'HC':($special==3?'RE':($special==4?'HR':($special==5?'OT':($special==6?'FR':'DC'))))),
                                'soluong'=>$soluong,
                                'sotan'=>$sotan>0?$sotan:($tons>0?$tons:0),

                            );
                            $this->alertMail($content_mail);
                        }

                    }

                }
                

                if ($normal != -1 && $normal != null && $special != -1) {
                    if ($special == 3 || $special == 4 || $special == 5 || $special == 6) {
                        $fix = 1.5;
                    }
                    

                    if ($normal == 20) {
                        $transports = $transport_model->getTransportByField('c20_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c20_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }

                        if ($transports == null) {
                            
                            
                            //echo ($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1) ;
                            $data = array(
                                'fix' => 0,
                                'bo' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1),
                                'thuy' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1),
                                'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                'from' => $loc_from,
                                'to' => $loc_to,
                                'data' => $dt,
                                );
                            echo json_encode($data);
                            //return false;
                        }
                        elseif($transports != null){
                            
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                $data = array(
                                    'fix' => ((($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt)*1.15,
                                    'bo' => (($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    'data' => $dt,
                                    );
                                echo json_encode($data);
                                //return false;
                            }
                        }
                        else{
                            $data = array(
                                        'fix' => 0,
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        'data' => $dt,
                                        );
                                    echo json_encode($data);
                                //return false;
                        }
                        
                        
                    }
                    else if($normal == 40){
                        $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }
                        if ($transports == null) {
                            
                            //echo ($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1) ;
                            $data = array(
                                    'fix' => 0,
                                    'bo' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1),
                                    'thuy' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1),
                                    'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    'data' => $dt,
                                    );
                            //var_dump($trans_to);die();
                                echo json_encode($data);
                            //return false;
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->c40_feet)*$soluong*$fix*($sotan>30?5:1);
                                $data = array(
                                    'fix' => ((($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt)*1.15,
                                    'bo' => (($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    'data' => $dt,
                                    );
                                echo json_encode($data);
                                //return false;
                            }
                        }
                        else{
                            $data = array(
                                        'fix' => 0,
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        'data' => $dt,
                                        );
                                    echo json_encode($data);
                                //return false;
                        }
                    }
                    else if($normal == 45){
                        $transports = $transport_model->getTransportByField('c45_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c45_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }
                        if ($transports == null) {
                            
                            //echo ($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1) ;
                            $data = array(
                                    'fix' => 0,
                                    'bo' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1),
                                    'thuy' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1),
                                    'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    'data' => $dt,
                                    );
                                echo json_encode($data);
                            //return false;
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+($transport->c40_feet+300000))*$soluong*$fix*($sotan>30?5:1);
                                $data = array(
                                    'fix' => ((($sotan<=20?($transport->c45_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c45_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c45_feet)*2:$transport->c45_feet))))*$soluong*$fix+$opt)*1.15,
                                    'bo' => (($sotan<=20?($transport->c45_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c45_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c45_feet)*2:$transport->c45_feet))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?($transport->c45_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c45_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c45_feet)*2:$transport->c45_feet))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    'data' => $dt,
                                    );
                                echo json_encode($data);
                                //return false;
                            }
                        }
                        else{
                            $data = array(
                                        'fix' => 0,
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        'data' => $dt,
                                        );
                                    echo json_encode($data);
                                //return false;
                        }
                    }
                    else if($normal == 220){
                        $transports = $transport_model->getTransportByField('c2x20_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c2x20_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }
                        if ($transports == null) {
                            
                            //echo ($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1) ;
                            $data = array(
                                    'fix' => 0,
                                    'bo' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1),
                                    'thuy' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1),
                                    'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    'data' => $dt,
                                    );
                                echo json_encode($data);
                            //return false;
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+($transport->c40_feet+300000))*$soluong*$fix*($sotan>30?5:1);
                                $data = array(
                                    'fix' => ((($sotan<=20?($transport->c2x20_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c2x20_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c2x20_feet)*2:$transport->c2x20_feet))))*$soluong*$fix+$opt)*1.15,
                                    'bo' => (($sotan<=20?($transport->c2x20_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c2x20_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c2x20_feet)*2:$transport->c2x20_feet))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?($transport->c2x20_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c2x20_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c2x20_feet)*2:$transport->c2x20_feet))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    'data' => $dt,
                                    );
                                echo json_encode($data);
                                //return false;
                            }
                        }
                        else{
                            $data = array(
                                        'fix' => 0,
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        'data' => $dt,
                                        );
                                    echo json_encode($data);
                                //return false;
                        }
                    }
                }
                
                elseif ($tons != null) {
                    if ($tons > 0 && $tons <= 20) {
                        $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }
                        if ($transports == null) {
                            
                            //echo ($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1) ;
                            $data = array(
                                    'fix' => 0,
                                    'bo' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong,
                                    'thuy' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong,
                                    'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    'data' => $dt,
                                    );
                                echo json_encode($data);
                            //return false;
                        }
                        else if($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->c20_ton)*$soluong;
                                $data = array(
                                        'fix' => (($transport->c40_feet-200000)*$soluong+$opt)*1.15,
                                        'bo' => ($transport->c40_feet-200000)*$soluong+$opt,
                                        'thuy' => ($transport->c40_feet-200000)*$soluong+$opt,
                                        'err' => null,
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        'data' => $dt,
                                        );
                                    echo json_encode($data);
                                //return false;
                            }
                        }
                        else{
                            $data = array(
                                        'fix' => 0,
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        'data' => $dt,
                                        );
                                    echo json_encode($data);
                                //return false;
                        }
                        
                    }
                    elseif ($tons > 20 && $tons < 29) {
                        $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }
                        if ($transports == null) {
                            
                            //echo ($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1) ;
                            $data = array(
                                    'fix' => 0,
                                    'bo' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong,
                                    'thuy' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong,
                                    'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    'data' => $dt,
                                    );
                            //var_dump('dsdsd');
                                echo json_encode($data);
                            //return false;
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->c28_ton)*$soluong;
                                $data = array(
                                        'fix' => (($transport->c40_feet)*$soluong+$opt)*1.15,
                                        'bo' => ($transport->c40_feet)*$soluong+$opt,
                                        'thuy' => ($transport->c40_feet)*$soluong+$opt,
                                        'err' => null,
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        'data' => $dt,
                                        );
                                    echo json_encode($data);
                                //return false;
                            }
                        }
                        else{
                            $data = array(
                                        'fix' => 0,
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        'data' => $dt,
                                        );
                                    echo json_encode($data);
                                //return false;
                        }
                        
                    }
                    elseif ($tons >= 29 && $quatai == null) {
                        $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }
                        if ($transports == null) {
                            
                            //echo ($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1) ;
                            $data = array(
                                    'fix' => 0,
                                    'bo' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong,
                                    'thuy' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong,
                                    'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    'data' => $dt,
                                    );
                                echo json_encode($data);
                            //return false;
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->over_28_ton)*$soluong;
                                $data = array(
                                        'fix' => ((round($transport->c40_feet/29*$tons))*$soluong+$opt)*1.15,
                                        'bo' => (round($transport->c40_feet/29*$tons))*$soluong+$opt,
                                        'thuy' => (round($transport->c40_feet/29*$tons))*$soluong+$opt,
                                        'err' => null,
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        'data' => $dt,
                                        );
                                    echo json_encode($data);
                                //return false;
                            }
                        }
                        else{
                            $data = array(
                                        'fix' => 0,
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        'data' => $dt,
                                        );
                                    echo json_encode($data);
                                //return false;
                        }
                        
                    }
                    elseif ($tons >= 29 && $quatai == 1) {
                        $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }
                        if ($transports == null) {
                            
                            //echo ($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1) ;
                            $data = array(
                                    'fix' => 0,
                                    'bo' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*5,
                                    'thuy' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*5,
                                    'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    'data' => $dt,
                                    );
                                echo json_encode($data);
                            //return false;
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->over_28_ton)*$soluong*5;
                                $data = array(
                                        'fix' => (($transport->c40_feet*2)*$soluong+$opt)*1.15,
                                        'bo' => ($transport->c40_feet*2)*$soluong+$opt,
                                        'thuy' => ($transport->c40_feet*2)*$soluong+$opt,
                                        'err' => null,
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        'data' => $dt,
                                        );
                                    echo json_encode($data);
                                //return false;
                            }
                        }
                        else{
                            $data = array(
                                        'fix' => 0,
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        'data' => $dt,
                                        );
                                    echo json_encode($data);
                                //return false;
                        }
                        
                    }
                    else{
                        $data = array(
                                    'fix' => 0,
                                    'bo' => 0,
                                    'thuy' => 0,
                                    'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    'data' => $dt,
                                    );
                                echo json_encode($data);
                        //return false;
                    }
                        
                }

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                if($data['bo'] > 0){

                    if (isset($_COOKIE['cus_email']) && $_COOKIE['cus_email'] != '' && !isset($_SESSION['userid_logined'])) {
                      
                      $location_from = $location_model->getLocationByWhere(array('location_id'=>$loc_from))->location_name;
                      $location_to = $location_model->getLocationByWhere(array('location_id'=>$loc_to))->location_name;

                        
                        $congty = $e_customer->getCustomerByWhere(array('e_customer_email'=>$_COOKIE['cus_email']));
                        $tencongty = $congty->e_customer_co;
                        $emailcongty = $congty->e_customer_email;
                        
                        $this->sendMail(array('type'=>($special != null && $special != -1)?$special:1,'from'=>$loc_from,'to'=>$loc_to,'emailcongty'=>$emailcongty,'tencongty'=>$tencongty,'diemdi'=>$location_from,'diemden'=>$location_to,'gia'=>$this->lib->formatMoney($data['fix']),'container'=>(($normal != -1 && $normal != null && $special != -1)?$normal:40).' feet','soluong'=>$soluong,'tan'=>($tons!=null)?$tons:$sotan));
                    }
                    else if (isset($_SESSION['userid_logined']) && $_SESSION['role_logined'] == 4 ) {
                        
                        if($sotan > 0){
                            $sale_model = $this->model->get('contsaleModel');

                            $sale_data = array(
                                'sale' => $_SESSION['userid_logined'],
                                'start_date' => strtotime(date('d-m-Y')),
                                'loc_from' => $loc_from,
                                'loc_to' => $loc_to,
                                'customer' => $id_customer,
                                'size' => $normal,
                                'type' => $special,
                                'number' => $soluong,
                                'ton' => ($tons!=null)?$tons:$sotan,
                                'price' => $data['fix'],
                                'status' => 0,

                            );

                            $sale_model->createSale($sale_data);
                        }
                        if($tons > 0){
                            $sale_model = $this->model->get('tonsaleModel');

                            $sale_data = array(
                                'sale' => $_SESSION['userid_logined'],
                                'start_date' => strtotime(date('d-m-Y')),
                                'loc_from' => $loc_from,
                                'loc_to' => $loc_to,
                                'customer' => $id_customer,
                                'type' => $loaihang,
                                'number' => $soluong,
                                'ton' => $tons,
                                'price' => $data['fix'],
                                'status' => 0,

                            );

                            $sale_model->createSale($sale_data);
                        }

                    }
                }
                
                //$this->view->data['transports'] = $transport_model->getAllTransport($data);
            }
            
        }
    }

    public function getManifest(){
        if(isset($_SERVER['HTTP_ORIGIN'])){
            switch ($_SERVER['HTTP_ORIGIN']) {
                case 'http://tancangmientrung.com': case 'https://tancangmientrung.com': case 'http://demo.vantaidaphuongthuc.com': case 'http://tancangmientrung.local':
                header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
                header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
                header('Access-Control-Max-Age: 1000');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
                break;
            }
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $type_1 = isset($_POST['type_1']) ? $_POST['type_1'] : null;
            $type_2 = isset($_POST['type_2']) ? $_POST['type_2'] : null;
            $sl = isset($_POST['manifest']) ? $_POST['manifest'] : null;
            $manifest_port = isset($_POST['manifest_port']) ? $_POST['manifest_port'] : null;

            if ($type_1 != null || $type_2 != null) {
                $manifest_model = $this->model->get('manifestModel');
                
                if ($type_1 != -1) {
                    $manifests = $manifest_model->getManifestByField('hcm,caimep','manifest_id = '.$type_1);
                    foreach ($manifests as $manifest) {
                        if($manifest_port == 'hcm')
                            $data = $manifest->hcm*$sl;
                        elseif ($manifest_port == 'caimep') 
                            $data = $manifest->caimep*$sl;
                    }
                    
                }
                else if ($type_2 != -1) {
                    $manifests = $manifest_model->getManifestByField('hcm,caimep','manifest_id = '.$type_2);
                    foreach ($manifests as $manifest) {
                        if($manifest_port == 'hcm')
                            $data = $manifest->hcm*$sl;
                        elseif ($manifest_port == 'caimep') 
                            $data = $manifest->caimep*$sl;
                    }
                    
                }
                echo $data;

                if (isset($_SESSION['userid_logined']) && $_SESSION['role_logined'] == 4 ) {
                    $customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : null;
                    $customer_email = isset($_POST['customer_email']) ? $_POST['customer_email'] : null;
                    $customer_phone = isset($_POST['customer_phone']) ? $_POST['customer_phone'] : null;

                    $customer_model = $this->model->get('customerModel');

                    if ($customer_name != null) {
                        $cus_data = array(
                            'customer_name' => trim($customer_name),
                            'customer_email' => trim($customer_email),
                            'customer_phone' => trim($customer_phone),
                        );
                        if ($customer_model->getCustomerByWhere(array('customer_name'=>$cus_data['customer_name']))) {
                            $id_customer = $customer_model->getCustomerByWhere(array('customer_name'=>$cus_data['customer_name']))->customer_id;
                        }
                        else{
                            $customer_model->createCustomer($cus_data);
                            $id_customer = $customer_model->getLastCustomer()->customer_id;
                        }
                    }
                    else{
                        $id_customer = 0;
                    }

                    $sale_model = $this->model->get('manifestsaleModel');

                            $sale_data = array(
                                'sale' => $_SESSION['userid_logined'],
                                'start_date' => strtotime(date('d-m-Y')),
                                'port' => $manifest_port=='hcm'?1:($manifest_port=='caimep'?2:0),
                                'type' => ($type_1 != -1)?$type_1:$type_2,
                                'number' => $sl,
                                'price' => $data,
                                'customer' => $id_customer,
                                'status' => 0,

                            );

                            $sale_model->createSale($sale_data);
                }
            }
            
        }
    }

    public function importexport(){
        if(isset($_SERVER['HTTP_ORIGIN'])){
            switch ($_SERVER['HTTP_ORIGIN']) {
                case 'http://tancangmientrung.com': case 'https://tancangmientrung.com': case 'http://demo.vantaidaphuongthuc.com': case 'http://tancangmientrung.local':
                header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
                header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
                header('Access-Control-Max-Age: 1000');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
                break;
            }
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            

                if (isset($_SESSION['userid_logined']) && $_SESSION['role_logined'] == 4 ) {
                    $type = isset($_POST['type']) ? $_POST['type'] : null;
                    $number = isset($_POST['number']) ? $_POST['number'] : null;
                    $price = isset($_POST['price']) ? $_POST['price'] : null;

                    $customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : null;
                    $customer_email = isset($_POST['customer_email']) ? $_POST['customer_email'] : null;
                    $customer_phone = isset($_POST['customer_phone']) ? $_POST['customer_phone'] : null;

                    $customer_model = $this->model->get('customerModel');

                    if ($customer_name != null) {
                        $cus_data = array(
                            'customer_name' => trim($customer_name),
                            'customer_email' => trim($customer_email),
                            'customer_phone' => trim($customer_phone),
                        );
                        if ($customer_model->getCustomerByWhere(array('customer_name'=>$cus_data['customer_name']))) {
                            $id_customer = $customer_model->getCustomerByWhere(array('customer_name'=>$cus_data['customer_name']))->customer_id;
                        }
                        else{
                            $customer_model->createCustomer($cus_data);
                            $id_customer = $customer_model->getLastCustomer()->customer_id;
                        }
                    }
                    else{
                        $id_customer = 0;
                    }

                    $sale_model = $this->model->get('imexsaleModel');

                            $sale_data = array(
                                'sale' => $_SESSION['userid_logined'],
                                'start_date' => strtotime(date('d-m-Y')),
                                'type' => $type,
                                'number' => $number,
                                'price' => $price,
                                'customer' => $id_customer,
                                'status' => 0,

                            );

                            $sale_model->createSale($sale_data);
                }
            }
            
        }
    
    public function chuyencang(){
        if(isset($_SERVER['HTTP_ORIGIN'])){
            switch ($_SERVER['HTTP_ORIGIN']) {
                case 'http://tancangmientrung.com': case 'https://tancangmientrung.com': case 'http://demo.vantaidaphuongthuc.com': case 'http://tancangmientrung.local':
                header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
                header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
                header('Access-Control-Max-Age: 1000');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
                break;
            }
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            

                if (isset($_SESSION['userid_logined']) && $_SESSION['role_logined'] == 4 ) {
                    $type = isset($_POST['type']) ? $_POST['type'] : null;
                    $number = isset($_POST['number']) ? $_POST['number'] : null;
                    $price = isset($_POST['price']) ? $_POST['price'] : null;

                    $customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : null;
                    $customer_email = isset($_POST['customer_email']) ? $_POST['customer_email'] : null;
                    $customer_phone = isset($_POST['customer_phone']) ? $_POST['customer_phone'] : null;

                    $customer_model = $this->model->get('customerModel');

                    if ($customer_name != null) {
                        $cus_data = array(
                            'customer_name' => trim($customer_name),
                            'customer_email' => trim($customer_email),
                            'customer_phone' => trim($customer_phone),
                        );
                        if ($customer_model->getCustomerByWhere(array('customer_name'=>$cus_data['customer_name']))) {
                            $id_customer = $customer_model->getCustomerByWhere(array('customer_name'=>$cus_data['customer_name']))->customer_id;
                        }
                        else{
                            $customer_model->createCustomer($cus_data);
                            $id_customer = $customer_model->getLastCustomer()->customer_id;
                        }
                    }
                    else{
                        $id_customer = 0;
                    }

                    $sale_model = $this->model->get('portsaleModel');

                            $sale_data = array(
                                'sale' => $_SESSION['userid_logined'],
                                'start_date' => strtotime(date('d-m-Y')),
                                'type' => $type,
                                'number' => $number,
                                'price' => $price,
                                'customer' => $id_customer,
                                'status' => 0,

                            );

                            $sale_model->createSale($sale_data);
                }
            }
            
        }
    

    public function getShipping(){
        if(isset($_SERVER['HTTP_ORIGIN'])){
            switch ($_SERVER['HTTP_ORIGIN']) {
                case 'http://tancangmientrung.com': case 'https://tancangmientrung.com': case 'http://demo.vantaidaphuongthuc.com': case 'http://tancangmientrung.local':
                header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
                header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
                header('Access-Control-Max-Age: 1000');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
                break;
            }
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $loc_from = isset($_POST['loc_from']) ? $_POST['loc_from'] : null;
            $loc_to = isset($_POST['loc_to']) ? $_POST['loc_to'] : null;


            if ($loc_from != null || $loc_to != null) {
                $shipping_model = $this->model->get('shippingModel');
                
                $shippings = $shipping_model->getShippingByField('c20_feet,c40_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                if($shippings){
                    foreach ($shippings as $shipping) {
                        $data['shipping_c20'] = $shipping->c20_feet;
                        $data['shipping_c40'] = $shipping->c40_feet;
                    }
                }
                else{
                    $data['shipping_c20'] = 0;
                    $data['shipping_c40'] = 0;
                }
                echo json_encode($data);
            }
            
        }
    }

     public function getHandling(){
        if(isset($_SERVER['HTTP_ORIGIN'])){
            switch ($_SERVER['HTTP_ORIGIN']) {
                case 'http://tancangmientrung.com': case 'https://tancangmientrung.com': case 'http://demo.vantaidaphuongthuc.com': case 'http://tancangmientrung.local':
                header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
                header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
                header('Access-Control-Max-Age: 1000');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
                break;
            }
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $port = isset($_POST['port']) ? $_POST['port'] : null;
            $truck_barge = isset($_POST['truck_barge']) ? $_POST['truck_barge'] : null;
            $lift = isset($_POST['lift']) ? $_POST['lift'] : null;
            $status = isset($_POST['status']) ? $_POST['status'] : null;
            $handling_cont_type = isset($_POST['handling_cont_type']) ? $_POST['handling_cont_type'] : null;


            if ($port != null && $truck_barge != null && $lift != null && $status != null && $handling_cont_type != null ) {

                $handling_model = $this->model->get('handlingModel');
                
                $handlings = $handling_model->getHandlingByField('c20_feet,c40_feet,c45_feet','truck_barge = '.$truck_barge.' AND lift = '.$lift.' AND status = '.$status.' AND port = '.$port);
                    if ($handlings) {
                        foreach ($handlings as $handling) {
                            if ($handling_cont_type == 20) {
                                $data['price'] = $handling->c20_feet;
                                break;
                            }
                            else if ($handling_cont_type == 40) {
                                $data['price'] = $handling->c40_feet;
                                break;
                            }
                            else if ($handling_cont_type == 45) {
                                $data['price'] = $handling->c45_feet;
                                break;
                            }
                        }
                    }
                    elseif (!$handlings){
                        $data['price'] = 0;
                        $data['err'] = "Bảng giá đang được cập nhật. Hãy liên hệ với chúng tôi để được hỗ trợ !";
                    }
                    

                

                echo json_encode($data);

                if (isset($_SESSION['userid_logined']) && $_SESSION['role_logined'] == 4 ) {
                    $type = isset($_POST['type']) ? $_POST['type'] : null;
                    $number = isset($_POST['number']) ? $_POST['number'] : null;
                    $price = isset($_POST['price']) ? $_POST['price'] : null;

                    $customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : null;
                    $customer_email = isset($_POST['customer_email']) ? $_POST['customer_email'] : null;
                    $customer_phone = isset($_POST['customer_phone']) ? $_POST['customer_phone'] : null;

                    $customer_model = $this->model->get('customerModel');

                    if ($customer_name != null) {
                        $cus_data = array(
                            'customer_name' => trim($customer_name),
                            'customer_email' => trim($customer_email),
                            'customer_phone' => trim($customer_phone),
                        );
                        if ($customer_model->getCustomerByWhere(array('customer_name'=>$cus_data['customer_name']))) {
                            $id_customer = $customer_model->getCustomerByWhere(array('customer_name'=>$cus_data['customer_name']))->customer_id;
                        }
                        else{
                            $customer_model->createCustomer($cus_data);
                            $id_customer = $customer_model->getLastCustomer()->customer_id;
                        }
                    }
                    else{
                        $id_customer = 0;
                    }

                    $sale_model = $this->model->get('liftsaleModel');

                            $sale_data = array(
                                'sale' => $_SESSION['userid_logined'],
                                'start_date' => strtotime(date('d-m-Y')),
                                'port' => $port,
                                'vehicle' => $truck_barge,
                                'lift' => $lift,
                                'status' => $status,
                                'size' => $handling_cont_type,
                                'price' => $data['price'],
                                'customer' => $id_customer,
                                'status' => 0,

                            );

                            $sale_model->createSale($sale_data);
                }

            }
            
        }
    }
    public function rent(){
        if(isset($_SERVER['HTTP_ORIGIN'])){
            switch ($_SERVER['HTTP_ORIGIN']) {
                case 'http://tancangmientrung.com': case 'https://tancangmientrung.com': case 'http://demo.vantaidaphuongthuc.com': case 'http://tancangmientrung.local':
                header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
                header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
                header('Access-Control-Max-Age: 1000');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
                break;
            }
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            

                if (isset($_SESSION['userid_logined']) && $_SESSION['role_logined'] == 4 ) {
                    $type = isset($_POST['type']) ? $_POST['type'] : null;
                    $number = isset($_POST['number']) ? $_POST['number'] : null;
                    $day = isset($_POST['day']) ? $_POST['day'] : null;
                    $price = isset($_POST['price']) ? $_POST['price'] : null;

                    $customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : null;
                    $customer_email = isset($_POST['customer_email']) ? $_POST['customer_email'] : null;
                    $customer_phone = isset($_POST['customer_phone']) ? $_POST['customer_phone'] : null;

                    $customer_model = $this->model->get('customerModel');

                    if ($customer_name != null) {
                        $cus_data = array(
                            'customer_name' => trim($customer_name),
                            'customer_email' => trim($customer_email),
                            'customer_phone' => trim($customer_phone),
                        );
                        if ($customer_model->getCustomerByWhere(array('customer_name'=>$cus_data['customer_name']))) {
                            $id_customer = $customer_model->getCustomerByWhere(array('customer_name'=>$cus_data['customer_name']))->customer_id;
                        }
                        else{
                            $customer_model->createCustomer($cus_data);
                            $id_customer = $customer_model->getLastCustomer()->customer_id;
                        }
                    }
                    else{
                        $id_customer = 0;
                    }

                    $sale_model = $this->model->get('rentsaleModel');

                            $sale_data = array(
                                'sale' => $_SESSION['userid_logined'],
                                'start_date' => strtotime(date('d-m-Y')),
                                'type' => $type,
                                'number' => $number,
                                'day' => $day,
                                'price' => $price,
                                'customer' => $id_customer,
                                'status' => 0,

                            );

                            $sale_model->createSale($sale_data);
                }
            }
            
        }
    
    public function buy(){
        if(isset($_SERVER['HTTP_ORIGIN'])){
            switch ($_SERVER['HTTP_ORIGIN']) {
                case 'http://tancangmientrung.com': case 'https://tancangmientrung.com': case 'http://demo.vantaidaphuongthuc.com': case 'http://tancangmientrung.local':
                header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
                header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
                header('Access-Control-Max-Age: 1000');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
                break;
            }
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            

                if (isset($_SESSION['userid_logined']) && $_SESSION['role_logined'] == 4 ) {
                    $type = isset($_POST['type']) ? $_POST['type'] : null;
                    $number = isset($_POST['number']) ? $_POST['number'] : null;
                    $price = isset($_POST['price']) ? $_POST['price'] : null;

                    $customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : null;
                    $customer_email = isset($_POST['customer_email']) ? $_POST['customer_email'] : null;
                    $customer_phone = isset($_POST['customer_phone']) ? $_POST['customer_phone'] : null;

                    $customer_model = $this->model->get('customerModel');

                    if ($customer_name != null) {
                        $cus_data = array(
                            'customer_name' => trim($customer_name),
                            'customer_email' => trim($customer_email),
                            'customer_phone' => trim($customer_phone),
                        );
                        if ($customer_model->getCustomerByWhere(array('customer_name'=>$cus_data['customer_name']))) {
                            $id_customer = $customer_model->getCustomerByWhere(array('customer_name'=>$cus_data['customer_name']))->customer_id;
                        }
                        else{
                            $customer_model->createCustomer($cus_data);
                            $id_customer = $customer_model->getLastCustomer()->customer_id;
                        }
                    }
                    else{
                        $id_customer = 0;
                    }

                    $sale_model = $this->model->get('buysaleModel');

                            $sale_data = array(
                                'sale' => $_SESSION['userid_logined'],
                                'start_date' => strtotime(date('d-m-Y')),
                                'type' => $type,
                                'number' => $number,
                                'price' => $price,
                                'customer' => $id_customer,
                                'status' => 0,

                            );

                            $sale_model->createSale($sale_data);
                }
            }
            
        }
    

    

    public function export(){
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $loc_from = isset($_POST['loc_from']) ? $_POST['loc_from'] : null;
            $loc_to = isset($_POST['loc_to']) ? $_POST['loc_to'] : null;
            $normal = isset($_POST['normal']) ? $_POST['normal'] : null;
            $special = isset($_POST['special']) ? $_POST['special'] : null;
            $tons = isset($_POST['tons']) ? $_POST['tons'] : 0;
            $soluong = isset($_POST['soluong']) ? $_POST['soluong'] : 0;
            $sotan = isset($_POST['sotan']) ? $_POST['sotan'] : 0;
            $fix = 1;
            //$opt = isset($_SESSION['userid_logined'])?0:100000;
            $opt = 0;

            $quatai = isset($_POST['quatai']) ? $_POST['quatai'] : null;

            if ($loc_from != null && $loc_to != null ) {
                $transport_model = $this->model->get('newtransportModel');
                $trans_from = 0;
                $trans_to = 0;

                if ($loc_from == $loc_to) {
                    
                    $data = array(
                        'fix' => 0,
                        'bo' => 0,
                        'thuy' => 0,
                        'err' => null,
                        );
                   
                }
                

                if ($normal != -1 && $normal != null && $special != -1) {
                    if ($special == 3 || $special == 4 || $special == 5 || $special == 6) {
                        $fix = 1.5;
                    }
                    

                    if ($normal == 20) {
                        $transports = $transport_model->getTransportByField('c20_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c20_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }

                        if ($transports == null) {
                            if ( ($loc_from > 1 && $loc_from < 7) && $loc_to > 6) {
                                $transports = $transport_model->getTransportByField('c20_feet','loc_from = 1 AND loc_to = '.$loc_to);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'fix' => ((500000+($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt)*1.15,
                                        'bo' => (500000+($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt,
                                        'thuy' => (500000+($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            elseif  (($loc_to > 1 && $loc_to < 7) && $loc_from > 6)  {
                                $transports = $transport_model->getTransportByField('c20_feet','loc_from = 1 AND loc_to = '.$loc_from);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'fix' => ((500000+($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt)*1.15,
                                        'bo' => (500000+($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt,
                                        'thuy' => (500000+($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            
                            
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                $data = array(
                                    'fix' => ((($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt)*1.15,
                                    'bo' => (($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    );
                                
                            }
                        }
                        else{
                            $data = array(
                                        'fix' => 0,
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        );
                                   
                        }
                        
                        
                    }
                    else if($normal == 40){
                        $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }
                        if ($transports == null) {
                            if ( ($loc_from > 1 && $loc_from < 7) && $loc_to > 6) {
                                $transports = $transport_model->getTransportByField('c40_feet','loc_from = 1 AND loc_to = '.$loc_to);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'fix' => ((500000+($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt)*1.15,
                                        'bo' => (500000+($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt,
                                        'thuy' => (500000+($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            elseif  (($loc_to > 1 && $loc_to < 7) && $loc_from > 6)  {
                                $transports = $transport_model->getTransportByField('c40_feet','loc_from = 1 AND loc_to = '.$loc_from);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'fix' => ((500000+($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt)*1.15,
                                        'bo' => (500000+($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt,
                                        'thuy' => (500000+($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            //echo ($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1) ;
                             
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->c40_feet)*$soluong*$fix*($sotan>30?5:1);
                                $data = array(
                                    'fix' => ((($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt)*1.15,
                                    'bo' => (($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    );
                                
                            }
                        }
                        else{
                            $data = array(
                                        'fix'=> 0,
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        );
                                  
                        }
                    }
                    else if($normal == 45){
                        $transports = $transport_model->getTransportByField('c45_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c45_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }
                        if ($transports == null) {
                            if ( ($loc_from > 1 && $loc_from < 7) && $loc_to > 6) {
                                $transports = $transport_model->getTransportByField('c45_feet','loc_from = 1 AND loc_to = '.$loc_to);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'fix' => ((500000+($sotan<=20?($transport->c45_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c45_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c45_feet)*2:($transport->c45_feet)))))*$soluong*$fix+$opt)*1.15,
                                        'bo' => (500000+($sotan<=20?($transport->c45_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c45_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c45_feet)*2:($transport->c45_feet)))))*$soluong*$fix+$opt,
                                        'thuy' => (500000+($sotan<=20?($transport->c45_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c45_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c45_feet)*2:($transport->c45_feet)))))*$soluong*$fix+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            elseif  (($loc_to > 1 && $loc_to < 7) && $loc_from > 6)  {
                                $transports = $transport_model->getTransportByField('c45_feet','loc_from = 1 AND loc_to = '.$loc_from);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'fix' => ((500000+($sotan<=20?($transport->c45_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c45_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c45_feet)*2:($transport->c45_feet)))))*$soluong*$fix+$opt)*1.15,
                                        'bo' => (500000+($sotan<=20?($transport->c45_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c45_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c45_feet)*2:($transport->c45_feet)))))*$soluong*$fix+$opt,
                                        'thuy' => (500000+($sotan<=20?($transport->c45_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c45_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c45_feet)*2:($transport->c45_feet)))))*$soluong*$fix+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            //echo ($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1) ;
                            
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+($transport->c40_feet+300000))*$soluong*$fix*($sotan>30?5:1);
                                $data = array(
                                    'fix' => ((($sotan<=20?($transport->c45_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c45_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c45_feet)*2:$transport->c45_feet))))*$soluong*$fix+$opt)*1.15,
                                    'bo' => (($sotan<=20?($transport->c45_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c45_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c45_feet)*2:$transport->c45_feet))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?($transport->c45_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c45_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c45_feet)*2:$transport->c45_feet))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    );
                                
                            }
                        }
                        else{
                            $data = array(
                                        'fix' => 0,
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        );
                                  
                        }
                    }
                    else if($normal == 220){
                        $transports = $transport_model->getTransportByField('c2x20_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c2x20_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }
                        if ($transports == null) {
                            if ( ($loc_from > 1 && $loc_from < 7) && $loc_to > 6) {
                                $transports = $transport_model->getTransportByField('c2x20_feet','loc_from = 1 AND loc_to = '.$loc_to);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'fix' => ((500000+($sotan<=20?($transport->c2x20_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c2x20_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c2x20_feet)*2:($transport->c2x20_feet)))))*$soluong*$fix+$opt)*1.15,
                                        'bo' => (500000+($sotan<=20?($transport->c2x20_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c2x20_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c2x20_feet)*2:($transport->c2x20_feet)))))*$soluong*$fix+$opt,
                                        'thuy' => (500000+($sotan<=20?($transport->c2x20_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c2x20_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c2x20_feet)*2:($transport->c2x20_feet)))))*$soluong*$fix+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            elseif  (($loc_to > 1 && $loc_to < 7) && $loc_from > 6)  {
                                $transports = $transport_model->getTransportByField('c2x20_feet','loc_from = 1 AND loc_to = '.$loc_from);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'fix' => ((500000+($sotan<=20?($transport->c2x20_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c2x20_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c2x20_feet)*2:($transport->c2x20_feet)))))*$soluong*$fix+$opt)*1.15,
                                        'bo' => (500000+($sotan<=20?($transport->c2x20_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c2x20_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c2x20_feet)*2:($transport->c2x20_feet)))))*$soluong*$fix+$opt,
                                        'thuy' => (500000+($sotan<=20?($transport->c2x20_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c2x20_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c2x20_feet)*2:($transport->c2x20_feet)))))*$soluong*$fix+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            //echo ($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1) ;
                            
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+($transport->c40_feet+300000))*$soluong*$fix*($sotan>30?5:1);
                                $data = array(
                                    'fix' => ((($sotan<=20?($transport->c2x20_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c2x20_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c2x20_feet)*2:$transport->c2x20_feet))))*$soluong*$fix+$opt)*1.15,
                                    'bo' => (($sotan<=20?($transport->c2x20_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c2x20_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c2x20_feet)*2:$transport->c2x20_feet))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?($transport->c2x20_feet)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c2x20_feet)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c2x20_feet)*2:$transport->c2x20_feet))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    );
                                
                            }
                        }
                        else{
                            $data = array(
                                        'fix' => 0,
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        );
                                  
                        }
                    }
                }
                
                elseif ($tons != null) {
                    if ($tons > 0 && $tons <= 20) {
                        $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }
                        if ($transports == null) {
                            if ( ($loc_from > 1 && $loc_from < 7) && $loc_to > 6) {
                                $transports = $transport_model->getTransportByField('c40_feet','loc_from = 1 AND loc_to = '.$loc_to);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'fix' => (500000+(($transport->c40_feet-200000)*$soluong)+$opt)*1.15,
                                        'bo' => 500000+(($transport->c40_feet-200000)*$soluong)+$opt,
                                        'thuy' => 500000+(($transport->c40_feet-200000)*$soluong)+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            elseif  (($loc_to > 1 && $loc_to < 7) && $loc_from > 6)  {
                                $transports = $transport_model->getTransportByField('c40_feet','loc_from = 1 AND loc_to = '.$loc_from);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'fix' => (500000+(($transport->c40_feet-200000)*$soluong)+$opt)*1.15,
                                        'bo' => 500000+(($transport->c40_feet-200000)*$soluong)+$opt,
                                        'thuy' => 500000+(($transport->c40_feet-200000)*$soluong)+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            //echo ($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1) ;
                            
                        }
                        else if($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->c20_ton)*$soluong;
                                $data = array(
                                        'fix' => (($transport->c40_feet-200000)*$soluong+$opt)*1.15,
                                        'bo' => ($transport->c40_feet-200000)*$soluong+$opt,
                                        'thuy' => ($transport->c40_feet-200000)*$soluong+$opt,
                                        'err' => null,
                                        );
                                   
                            }
                        }
                        else{
                            $data = array(
                                        'fix' => 0,
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        );
                                   
                        }
                        
                    }
                    elseif ($tons > 20 && $tons < 29) {
                        $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }
                        if ($transports == null) {
                            if ( ($loc_from > 1 && $loc_from < 7) && $loc_to > 6) {
                                $transports = $transport_model->getTransportByField('c40_feet','loc_from = 1 AND loc_to = '.$loc_to);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'fix' => (500000+(($transport->c40_feet)*$soluong)+$opt)*1.15,
                                        'bo' => 500000+(($transport->c40_feet)*$soluong)+$opt,
                                        'thuy' => 500000+(($transport->c40_feet)*$soluong)+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            elseif  (($loc_to > 1 && $loc_to < 7) && $loc_from > 6)  {
                                $transports = $transport_model->getTransportByField('c40_feet','loc_from = 1 AND loc_to = '.$loc_from);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'fix' => (500000+(($transport->c40_feet)*$soluong)+$opt)*1.15,
                                        'bo' => 500000+(($transport->c40_feet)*$soluong)+$opt,
                                        'thuy' => 500000+(($transport->c40_feet)*$soluong)+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            //echo ($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1) ;
                            
                                
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->c28_ton)*$soluong;
                                $data = array(
                                        'fix' => (($transport->c40_feet)*$soluong+$opt)*1.15,
                                        'bo' => ($transport->c40_feet)*$soluong+$opt,
                                        'thuy' => ($transport->c40_feet)*$soluong+$opt,
                                        'err' => null,
                                        );
                                   
                            }
                        }
                        else{
                            $data = array(
                                        'fix' => 0,
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        );
                                   
                        }
                        
                    }
                    elseif ($tons >= 29 && $quatai == null) {
                        $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }
                        if ($transports == null) {
                            if ( ($loc_from > 1 && $loc_from < 7) && $loc_to > 6) {
                                $transports = $transport_model->getTransportByField('c40_feet','loc_from = 1 AND loc_to = '.$loc_to);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'fix' => (500000+((round($transport->c40_feet/29*$tons))*$soluong)+$opt)*1.15,
                                        'bo' => 500000+((round($transport->c40_feet/29*$tons))*$soluong)+$opt,
                                        'thuy' => 500000+((round($transport->c40_feet/29*$tons))*$soluong)+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            elseif  (($loc_to > 1 && $loc_to < 7) && $loc_from > 6)  {
                                $transports = $transport_model->getTransportByField('c40_feet','loc_from = 1 AND loc_to = '.$loc_from);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'fix' => (500000+((round($transport->c40_feet/29*$tons))*$soluong)+$opt)*1.15,
                                        'bo' => 500000+((round($transport->c40_feet/29*$tons))*$soluong)+$opt,
                                        'thuy' => 500000+((round($transport->c40_feet/29*$tons))*$soluong)+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            //echo ($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1) ;
                            
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->over_28_ton)*$soluong;
                                $data = array(
                                        'fix' => ((round($transport->c40_feet/29*$tons))*$soluong+$opt)*1.15,
                                        'bo' => (round($transport->c40_feet/29*$tons))*$soluong+$opt,
                                        'thuy' => (round($transport->c40_feet/29*$tons))*$soluong+$opt,
                                        'err' => null,
                                        );
                                  
                            }
                        }
                        else{
                            $data = array(
                                        'fix' => 0,
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        );
                                   
                        }
                        
                    }
                    elseif ($tons >= 29 && $quatai == 1) {
                        $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }
                        if ($transports == null) {
                            if ( ($loc_from > 1 && $loc_from < 7) && $loc_to > 6) {
                                $transports = $transport_model->getTransportByField('c40_feet','loc_from = 1 AND loc_to = '.$loc_to);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'fix' => (500000+(($transport->c40_feet*2)*$soluong)+$opt)*1.15,
                                        'bo' => 500000+(($transport->c40_feet*2)*$soluong)+$opt,
                                        'thuy' => 500000+(($transport->c40_feet*2)*$soluong)+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            elseif  (($loc_to > 1 && $loc_to < 7) && $loc_from > 6)  {
                                $transports = $transport_model->getTransportByField('c40_feet','loc_from = 1 AND loc_to = '.$loc_from);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'fix' => (500000+(($transport->c40_feet*2)*$soluong)+$opt)*1.15,
                                        'bo' => 500000+(($transport->c40_feet*2)*$soluong)+$opt,
                                        'thuy' => 500000+(($transport->c40_feet*2)*$soluong)+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            //echo ($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1) ;
                             
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->over_28_ton)*$soluong*5;
                                $data = array(
                                        'fix' => (($transport->c40_feet*2)*$soluong+$opt)*1.15,
                                        'bo' => ($transport->c40_feet*2)*$soluong+$opt,
                                        'thuy' => ($transport->c40_feet*2)*$soluong+$opt,
                                        'err' => null,
                                        );
                                   
                            }
                        }
                        else{
                            $data = array(
                                        'fix' => 0,
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        );
                                   
                        }
                        
                    }
                    else{
                        $data = array(
                                    'fix' => 0,
                                    'bo' => 0,
                                    'thuy' => 0,
                                    'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                    );
                               
                    }
                        
                }
                
                //$this->view->data['transports'] = $transport_model->getAllTransport($data);
            }

            $location = $this->model->get('locationModel');
        $tuyendi = $location->getLocation($loc_from)->location_name;
        $tuyenden = $location->getLocation($loc_to)->location_name;
        if ($normal == 20) {
            $con_type = "20'";
        }
        elseif ($normal == 45) {
            $con_type = "45'";
        }
        else{
            $con_type = "40'";
        }
        if ($tons > 0) {
          $tan = $tons;
        }
        else if($sotan > 0){
          $tan = $sotan;
        }
        else{
          $tan = 0;
        }

        require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");
            /*require ('lib/Classes/PHPExcel/Writer/PDF.php');
            require ('lib/Classes/PHPExcel/Writer/PDF/DomPDF.php');*/
            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A7', 'BẢNG BÁO GIÁ VẬN CHUYỂN')
               ->setCellValue('A9', 'Công ty Cai Mep Glogbal Logistics xin gửi lời cám ơn đến quý khách hàng đã quan tâm và sử dụng dịch vụ của chúng tôi.')
               ->setCellValue('A10', 'Kính gửi quý khách hàng bảng báo giá dịch vụ vận chuyển container bằng xe đầu kéo như sau:')
               ->setCellValue('A11', 'STT')
               ->setCellValue('B11', 'Tuyến dịch vụ')
               ->setCellValue('D11', 'Tariff(VND)/'.$soluong.' cont/'.$tan.' tấn')
               ->setCellValue('D13', $con_type)
               ->setCellValue('A14', '1')
               ->setCellValue('B14', $tuyendi)
               ->setCellValue('C14', $tuyenden)
               ->setCellValue('D14', $data['fix'])
               ->setCellValue('A16', 'Ghi chú:')
               ->setCellValue('A17', "1.")
               ->setCellValue('B17', "Giá trên bao gồm:\n + Phí vận chuyển cont.")
               ->setCellValue('A18', "2.")
               ->setCellValue('B18', "Giá không bao gồm:\n + Thuế VAT 10%.\n + Bốc/dỡ hàng hoá, phí nâng/hạ và vệ sinh cont.\n + Tất cả các chi phí có hoá đơn và phát sinh khác (nếu có).")
               ->setCellValue('A19', "")
               ->setCellValue('B19', "")
               ->setCellValue('A20', "3.")
               ->setCellValue('B20', 'Mức giá trên sẽ thay đổi tùy theo biên độ dao động giá xăng dầu và mức giá ấn định bởi chính phủ.')
               ->setCellValue('A21', '* Chúng tôi cam kết mang đến cho quý khách hàng chất lượng dịch vụ, đảm bảo thời gian giao hàng tại cảng đích với giá cả cạnh tranh. Chúng tôi thực hiện dịch vụ 24h/ngày, 7 ngày/tuần.')
               ->setCellValue('A23', 'Mong rằng CMG và quý khách hàng sẽ có cơ hội hợp tác lâu dài và ổn định.')
               ->setCellValue('A25', 'Trụ sở chính  : 29 Quốc lộ 51, Ấp Đồng, Xã Phước Tân, TP.Biên Hòa, T.Đồng Nai')
               ->setCellValue('A26', 'VPĐD           :  Số 5/20,  đường 24, phường Hiệp Bình Chánh, quận Thủ Đức, TP. Hồ Chí Minh. ')
               ->setCellValue('A27', 'Chi nhánh      : Cổng khu cảng Cái Mép Thượng, xã Tân Phước, huyện Tân Thành, tỉnh BR-VT')
               ->setCellValue('A28', 'Điện thoại     : 083.500.9000                                   Fax: 0613.937.677  ')
               ->setCellValue('A29', 'Email            : sale@cmglogistics.com.vn               Website: www.cmglogistics.com.vn - www.caimeptrading.com');

            

            $objRichText = new PHPExcel_RichText();
            $textBold = $objRichText->createTextRun("CAI MEP GLOBAL LOGISTICS\n");
            $textBold->getFont()->getColor()->setARGB('022D55');
            $textBold->getFont()->setSize(20);
            $textBold->getFont()->setBold(true);
            $textBold->getFont()->setName('Times New Roman');

            $under = $objRichText->createTextRun('Carrier ');
            $under->getFont()->getColor()->setARGB('FF0000');
            $under->getFont()->setSize(20);
            
            $under->getFont()->setBold(true);
            $under->getFont()->setName('Times New Roman');
            
            $nor = $objRichText->createTextRun('Managerment Group');
            $nor->getFont()->getColor()->setARGB('022D55');
            $nor->getFont()->setSize(20);
            
            $nor->getFont()->setBold(true);
            $nor->getFont()->setName('Times New Roman');

            $objPHPExcel->getActiveSheet()->getCell('A1')->setValue($objRichText);
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setWrapText(true);

            
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            

            $objDrawing = new PHPExcel_Worksheet_Drawing();
            $objDrawing->setName("name");
            $objDrawing->setDescription("Description");

            $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());

            $logo = "public/img/cmg.jpg";
            $objDrawing->setPath($logo);
            $objDrawing->setHeight(96);  
            $objDrawing->setWidth(200);    
            $objDrawing->setCoordinates('B1');

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Cai Mep Global Logistics")
                            ->setLastModifiedBy('CMG')
                            ->setTitle("Quotation")
                            ->setSubject("Quotation")
                            ->setDescription("Quotation")
                            ->setKeywords("Quotation")
                            ->setCategory("Quotation");
            $objPHPExcel->getActiveSheet()->setTitle("Quotation");

            $objPHPExcel->getActiveSheet()->getStyle("A1:F29")->getFont()->setName('Times New Roman');
            $objPHPExcel->getActiveSheet()->getStyle('A11:E14')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A16')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A23')->getFont()->setItalic(true);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->mergeCells('A1:F5');
            $objPHPExcel->getActiveSheet()->mergeCells('A7:F7');
            $objPHPExcel->getActiveSheet()->mergeCells('A9:E9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:F10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:A13');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:C13');
            $objPHPExcel->getActiveSheet()->mergeCells('D11:E12');
            $objPHPExcel->getActiveSheet()->mergeCells('D13:E13');
            $objPHPExcel->getActiveSheet()->mergeCells('D14:E14');
            $objPHPExcel->getActiveSheet()->mergeCells('A16:F16');
            $objPHPExcel->getActiveSheet()->mergeCells('B17:F17');
            $objPHPExcel->getActiveSheet()->mergeCells('B18:F18');
            $objPHPExcel->getActiveSheet()->mergeCells('B19:F19');
            $objPHPExcel->getActiveSheet()->mergeCells('B20:F20');
            $objPHPExcel->getActiveSheet()->mergeCells('A21:F21');
            $objPHPExcel->getActiveSheet()->mergeCells('A23:F23');
            $objPHPExcel->getActiveSheet()->mergeCells('A25:F25');
            $objPHPExcel->getActiveSheet()->mergeCells('A26:F26');
            $objPHPExcel->getActiveSheet()->mergeCells('A27:F27');
            $objPHPExcel->getActiveSheet()->mergeCells('A28:F28');
            $objPHPExcel->getActiveSheet()->mergeCells('A29:F29');

            $objPHPExcel->getActiveSheet()->getStyle("A7")->getFont()->setSize(20);
            $objPHPExcel->getActiveSheet()->getStyle("A9:F23")->getFont()->setSize(14);
            $objPHPExcel->getActiveSheet()->getStyle("A25:A29")->getFont()->setSize(14);
            $objPHPExcel->getActiveSheet()->getStyle("A16")->getFont()->setUnderline(PHPExcel_Style_Font::UNDERLINE_SINGLE);
            $objPHPExcel->getActiveSheet()->getStyle('D14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");

            $objPHPExcel->getActiveSheet()->getStyle('A1:F29')->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getStyle('A11:E14')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A11:E14')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A23')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A23')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A17:A20')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objPHPExcel->getActiveSheet()->getStyle('A17:A20')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $objPHPExcel->getActiveSheet()->getStyle('B17:B20')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->getActiveSheet()->getStyle('B17:B20')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);

            $objPHPExcel->getActiveSheet()->getStyle('A9')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $objPHPExcel->getActiveSheet()->getStyle('A10')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);

            $objPHPExcel->getActiveSheet()->getStyle('A1:F29')->applyFromArray(
                array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => array('argb' => 'FFFFFF'),
                        ),
                    ),
                    
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('A11:E14')->applyFromArray(
                array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => array('argb' => '000000'),
                        ),
                    ),
                    
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('A11:E13')->applyFromArray(
                array(
                    
                    'fill' => array(
                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => '119883')
                    )
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('A25:F29')->applyFromArray(
                array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => array('argb' => 'B4BB72'),
                        ),
                    ),
                    'fill' => array(
                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => 'B4BB72')
                    )
                )
            );

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(7);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(45);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(45);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(19);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(13);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('7')->setRowHeight(100);
            $objPHPExcel->getActiveSheet()->getRowDimension('8')->setRowHeight(27.75);
            $objPHPExcel->getActiveSheet()->getRowDimension('9')->setRowHeight(47);
            $objPHPExcel->getActiveSheet()->getRowDimension('10')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('13')->setRowHeight(30);
            $objPHPExcel->getActiveSheet()->getRowDimension('14')->setRowHeight(37.5);
            $objPHPExcel->getActiveSheet()->getRowDimension('15')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('17')->setRowHeight(67);
            $objPHPExcel->getActiveSheet()->getRowDimension('18')->setRowHeight(82);
            $objPHPExcel->getActiveSheet()->getRowDimension('21')->setRowHeight(66);
            $objPHPExcel->getActiveSheet()->getRowDimension('22')->setRowHeight(45);
            $objPHPExcel->getActiveSheet()->getRowDimension('24')->setRowHeight(45);
            $objPHPExcel->getActiveSheet()->getRowDimension('25')->setRowHeight(22);
            $objPHPExcel->getActiveSheet()->getRowDimension('26')->setRowHeight(22);
            $objPHPExcel->getActiveSheet()->getRowDimension('27')->setRowHeight(22);
            $objPHPExcel->getActiveSheet()->getRowDimension('28')->setRowHeight(22);
            $objPHPExcel->getActiveSheet()->getRowDimension('29')->setRowHeight(22);

            $objPHPExcel->setActiveSheetIndex(0);

            /*$rendererName = PHPExcel_Settings::PDF_RENDERER_DOMPDF;
            //$rendererLibrary = 'tcPDF5.9';
            //$rendererLibrary = 'mPDF5.4';
            $rendererLibrary = 'domPDF0.6.0beta3';
            $rendererLibraryPath = 'lib/Classes/' . $rendererLibrary;

            //save as a PDF file
            

            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment;filename="Bao gia.pdf"');
            header('Cache-Control: max-age=0');
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'PDF');
            $objWriter->writeAllSheets();
            $objWriter->save('php://output');*/

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename=Bao gia.xlsx");
            header("Cache-Control: max-age=0");
            
            ob_clean();
            
            $objWriter->save('php://output');
            
        }

        

    }

    

    

    public function sendMail($data = array()){
      $noidung = '
                <table border="0" cellpadding="0" cellspacing="0" width="889" style="border-collapse:
 collapse;table-layout:fixed;width:667pt">
 <tbody>
   <tr style="color:#022D55; text-align: center; font-size: 22px; font-weight: bold;">
   <td><img width="100%" height="83" src="'.BASE_URL.'/public/img/cmg.jpg" alt="Cai Mep Global Logistics" ></td>
   <td colspan="4">CAI MEP GLOBAL LOGISTICS <br> <span style="color:#FF0000">Carrier</span> Managerment Group</td>
   </tr>
 
 <tr height="133" style="mso-height-source:userset;height:99.95pt; font-size: 22px; font-weight: bold; text-align: center;">
  <td colspan="6" height="133" class="xl70" width="889" style="mso-style-parent:style0;">BẢNG BÁO GIÁ VẬN CHUYỂN</td>
 </tr>
 
 <tr height="62" style="mso-height-source:userset;height:10pt">
  <td colspan="5" height="62" class="xl71" width="882" style="height:10pt;width:662pt">Công
  ty Cai Mep Glogbal Logistics xin gửi lời cám ơn
  đến quý khách hàng đã quan tâm và sử dụng
  dịch vụ của chúng tôi.</td>
  <td class="xl66" width="7" style="border-top:none;border-left:none;width:5pt">&nbsp;</td>
 </tr>
 <tr height="86" style="mso-height-source:userset;height:45.1pt">
  <td colspan="6" height="86" class="xl71" width="889" style="height:45.1pt;width:667pt">Kính
  gửi quý khách hàng bảng báo giá dịch vụ vận
  chuyển container bằng xe đầu kéo như sau:</td>
 </tr>
 <tr ><td colspan="6"><table width="100%" border="1">
   <tr height="25" style="height:18.75pt; background: #119883;">
    <th rowspan="2" height="90" class="xl72" width="49" style="height:40pt;width:37pt">STT</th>
    <th colspan="2" rowspan="2" class="xl72" width="630" style="width:472pt">Tuyến
    dịch vụ</th>
    <th colspan="2" rowspan="1" class="xl72" width="203" style="width:153pt">Tariff(VNĐ)/'.$data['soluong'].' cont/'.$data['tan'].' tấn</th>
    
   </tr>
   <tr height="40" style="mso-height-source:userset;height:10.0pt; background: #119883;">
    <th colspan="2" height="40" class="xl72" width="203" style="height:10.0pt;width:153pt">'.$data['container'].'</th>
    
   </tr>
   <tr height="50" style="mso-height-source:userset;height:37.5pt; text-align:center">
    <td height="50" class="xl68" width="49" style="height:37.5pt;border-top:none;
    width:37pt">1</td>
    <td class="xl68" width="315" style="width:236pt">'.$data['diemdi'].'</td>
    <td class="xl68" width="315" style="width:236pt">'.$data['diemden'].'</td>
    <td colspan="2" class="xl73" width="203" style="width:153pt">'.$data['gia'].'</td>
    
   </tr>
   </table>
   </td>
   </tr>

 <tr height="25" style="height:18.75pt">
  <td colspan="6" height="25" class="xl74" width="889" style="height:18.75pt;
  width:667pt">Ghi chú:</td>
 </tr>
 <tr height="89" style="mso-height-source:userset;height:40.95pt">
  <td height="89" class="xl67" width="49" style="height:40.95pt;border-top:none;
  width:37pt">1</td>
  <td colspan="5" class="xl75" width="840" style="border-left:none;width:630pt">Giá
  trên bao gồm:<br>
    <span style="mso-spacerun:yes">&nbsp;</span>+ Phí vận chuyển cont.</td>
 </tr>
 <tr height="109" style="mso-height-source:userset;height:81.95pt">
  <td height="109" class="xl67" width="49" style="height:81.95pt;border-top:none;
  width:37pt">2</td>
  <td colspan="5" class="xl75" width="840" style="border-left:none;width:630pt">Giá
  không bao gồm:<br>
    <span style="mso-spacerun:yes">&nbsp;</span>+ Thuế VAT 10%.<br>
    <span style="mso-spacerun:yes">&nbsp;</span>+ Bốc/dỡ hàng hoá, phí
  nâng/hạ và vệ sinh cont.<br>
    <span style="mso-spacerun:yes">&nbsp;</span>+ Tất cả các chi phí có
  hoá đơn và phát sinh khác (nếu có).</td>
 </tr>
 <tr height="25" style="height:18.75pt">
  <td height="25" class="xl67" width="49" style="height:18.75pt;border-top:none;
  width:37pt">3</td>
  <td colspan="5" class="xl75" width="840" style="border-left:none;width:630pt">Mức
  giá trên sẽ thay đổi tùy theo biên độ dao
  động giá xăng dầu và mức giá ấn
  định bởi chính phủ.</td>
 </tr>
 <tr height="88" style="mso-height-source:userset;height:66.0pt; font-weight:bold">
  <td colspan="6" height="88" class="xl66" width="889" style="height:66.0pt;width:667pt">***
  Nếu bạn đồng ý với mức giá trên, hãy bấm vào link sau để tiếp tục làm hợp đồng <a href="'.BASE_URL.'/quotation/contract/1/1/1/1/'.base64_encode($data['from'].'n').'-'.base64_encode($data['to'].'g').'-'.base64_encode($data['soluong'].'o').'-'.base64_encode($data['tan'].'t').'-'.base64_encode($data['type'].'o').'-'.base64_encode(substr($data['container'],0,2).'n').'-'.base64_encode(str_replace(',', '', $data['gia']).'it').'">Hợp đồng</a> <br>*** Hoặc cung cấp cho chúng tôi mức giá đề nghị của bạn tại đây  <a href="'.BASE_URL.'/quotation/suggest/1/1/1/1/'.base64_encode($data['from'].'n').'-'.base64_encode($data['to'].'g').'-'.base64_encode($data['soluong'].'o').'-'.base64_encode($data['tan'].'t').'-'.base64_encode($data['type'].'o').'-'.base64_encode(substr($data['container'],0,2).'n').'-'.base64_encode(str_replace(',', '', $data['gia']).'it').'"> Đề nghị giá</a>.</td>
 </tr>
 <tr height="88" style="mso-height-source:userset;height:40.0pt">
  <td colspan="6" height="88" class="xl66" width="889" style="height:40.0pt;width:667pt">*
  Chúng tôi cam kết mang đến cho quý khách hàng chất
  lượng dịch vụ, đảm bảo thời
  gian giao hàng tại cảng đích với giá cả
  cạnh tranh. Chúng tôi thực hiện dịch vụ
  24h/ngày, 7 ngày/tuần.</td>
 </tr>
 <tr height="25" style="height:18.75pt">
  <td colspan="6" height="25" class="xl76" width="889" style="height:18.75pt;
  width:667pt">Mong rằng CMG và quý khách hàng sẽ có cơ
  hội hợp tác lâu dài và ổn định.</td>
 </tr>
 <tr height="20" style="height:15.0pt">
 </tr>
 <tr height="29" style="mso-height-source:userset;height:21.95pt">
  <td colspan="6" height="29" class="xl77" width="889" style="height:21.95pt;
  width:667pt">Trụ sở chính<span style="mso-spacerun:yes">&nbsp;
  </span>: 29 Quốc lộ 51, Ấp Đồng, Xã
  Phước Tân, TP.Biên Hòa, T.Đồng Nai</td>
 </tr>
 <tr height="29" style="mso-height-source:userset;height:21.95pt">
  <td colspan="6" height="29" class="xl77" width="889" style="height:21.95pt;
  width:667pt">VPĐD<span style="mso-spacerun:yes">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>:<span style="mso-spacerun:yes">&nbsp; </span>Số 5/20,<span style="mso-spacerun:yes">&nbsp; </span>đường 24, phường
  Hiệp Bình Chánh, quận Thủ Đức, TP. Hồ Chí
  Minh.<span style="mso-spacerun:yes">&nbsp;</span></td>
 </tr>
 <tr height="29" style="mso-height-source:userset;height:21.95pt">
  <td colspan="6" height="29" class="xl77" width="889" style="height:21.95pt;
  width:667pt">Chi nhánh<span style="mso-spacerun:yes">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>:
  Cổng khu cảng Cái Mép Thượng, xã Tân Phước,
  huyện Tân Thành, tỉnh BR-VT</td>
 </tr>
 <tr height="29" style="mso-height-source:userset;height:21.95pt">
  <td colspan="6" height="29" class="xl77" width="889" style="height:21.95pt;
  width:667pt">Điện thoại<span style="mso-spacerun:yes">&nbsp;&nbsp;&nbsp;&nbsp;
  </span>: 083.500.9000<span style="mso-spacerun:yes">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>Fax:
  0613.937.677<span style="mso-spacerun:yes">&nbsp;&nbsp;</span></td>
 </tr>
 <tr height="29" style="mso-height-source:userset;height:21.95pt">
  <td colspan="6" height="29" class="xl77" width="889" style="height:21.95pt;
  width:667pt">Email<span style="mso-spacerun:yes">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>:
  sale@cmglogistics.com.vn<span style="mso-spacerun:yes">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
  </span>Website: www.cmglogistics.com.vn - www.caimeptrading.com</td>
 </tr>
 <!--[if supportMisalignedColumns]-->
 <tr height="0" style="display:none">
  <td width="49" style="width:37pt"></td>
  <td width="315" style="width:236pt"></td>
  <td width="315" style="width:236pt"></td>
  <td width="133" style="width:100pt"></td>
  <td width="70" style="width:53pt"></td>
  <td width="7" style="width:5pt"></td>
 </tr>
 <!--[endif]-->
</tbody></table>';

// Khai báo thư viên phpmailer
            require "lib/class.phpmailer.php";
             
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
            $mail->Host       = "smtp.gmail.com"; //host smtp để gửi mail
            $mail->Port       = 587; // cổng để gửi mail
            $mail->SMTPSecure = "tls"; //Phương thức mã hóa thư - ssl hoặc tls
            $mail->SMTPAuth   = true; //Xác thực SMTP
            $mail->CharSet = 'UTF-8';
            $mail->Username   = "caimeptrading.com@gmail.com"; // Tên đăng nhập tài khoản Gmail
            $mail->Password   = "caimeptrading!@#"; //Mật khẩu của gmail
            $mail->SetFrom("caimeptrading.com@gmail.com", "CMG"); // Thông tin người gửi
            $mail->AddReplyTo("sale@cmglogistics.com.vn","Sale CMG");// Ấn định email sẽ nhận khi người dùng reply lại.
            $mail->AddAddress($data['emailcongty'], $data['tencongty']);//Email của người nhận
            $mail->Subject = "BAO GIA DICH VU VAN TAI - CMG"; //Tiêu đề của thư
            $mail->IsHTML(true); // send as HTML   
            $mail->MsgHTML($noidung); //Nội dung của bức thư.
            // $mail->MsgHTML(file_get_contents("email-template.html"), dirname(__FILE__));
            // Gửi thư với tập tin html

            $mail->AltBody = "Báo giá dịch vụ vận chuyển - CMG";//Nội dung rút gọn hiển thị bên ngoài thư mục thư.
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
            
    }
    public function alertMail($data){
        require "lib/class.phpmailer.php";
             $noidung = '<p>Vừa có 1 khách hàng yêu cầu báo giá tuyến đường: <b> '.$data['tuyenduong'].'</b></p>
             <p> Container: <b>'.$data['feet'].'</b></p>
             <p> Loại container: <b>'.$data['loai'].'</b></p>
             <p> Số lượng: <b>'.$data['soluong'].'</b></p>
             <p> Số tấn/cont: <b>'.$data['sotan'].'</b></p>
             <p> Khách hàng: <b>'.$data['congty'].'</b></p>
             <p> Email: <b>'.$data['email'].'</b></p>
             <p> SĐT: <b>'.$data['sdt'].'</b></p>
             <p style="color:red; font-size: 16px; font-weight:bold;"> *** Vui lòng gửi file báo giá vào mail cho khách hàng đồng thời cập nhật vào hệ thống ***</p>';
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
            $mail->Host       = "smtp.gmail.com"; //host smtp để gửi mail
            $mail->Port       = 587; // cổng để gửi mail
            $mail->SMTPSecure = "tls"; //Phương thức mã hóa thư - ssl hoặc tls
            $mail->SMTPAuth   = true; //Xác thực SMTP
            $mail->CharSet = 'UTF-8';
            $mail->Username   = "caimeptrading.com@gmail.com"; // Tên đăng nhập tài khoản Gmail
            $mail->Password   = "caimeptrading!@#"; //Mật khẩu của gmail
            $mail->SetFrom("caimeptrading.com@gmail.com", "CMG"); // Thông tin người gửi
            $mail->AddReplyTo("sale@cmglogistics.com.vn","Sale CMG");// Ấn định email sẽ nhận khi người dùng reply lại.
            $mail->AddAddress('biz@caimeptrading.com', 'Biz CMG');//Email của người nhận
            $mail->Subject = "BÁO GIÁ TUYẾN ĐƯỜNG MỚI CHO KHÁCH HÀNG (GẤP)"; //Tiêu đề của thư
            $mail->IsHTML(true); // send as HTML   
            $mail->MsgHTML($noidung); //Nội dung của bức thư.
            // $mail->MsgHTML(file_get_contents("email-template.html"), dirname(__FILE__));
            // Gửi thư với tập tin html

            $mail->AltBody = "Báo giá dịch vụ vận chuyển - CMG";//Nội dung rút gọn hiển thị bên ngoài thư mục thư.
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
    }
    

}
?>