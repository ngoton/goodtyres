<?php
Class quotationController Extends baseController {
    public function index() {
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng giá dịch vụ vận tải, mở tờ khai, chuyển cảng, chỉnh manifest, mua bán container';

            $location_model = $this->model->get('locationModel');
            $district_model = $this->model->get('districtModel');
            $district = $district_model->getAllDistrict();
            $district_shipping = $district_model->getAllDistrict(array('where'=>'district_id in (SELECT loc_from FROM shipping) OR district_id in (SELECT loc_to FROM shipping)'));
            $this->view->data['districts'] = $district_shipping;
            /*$str = "";
            $location = $location_model->getAllLocation();
            $arr = array();
            foreach ($location as $loc) {
                $arr[$loc->district][] = $loc;
            }
            
            foreach ($district as $districts) {
                $str .= '<option value="" class="option_fix">'.$districts->district_name.'</option>';
                if (isset($arr[$districts->district_id])){
                    foreach ($arr[$districts->district_id] as $locations) {
                        $str .= '<option value="'.$locations->location_id.'">'."&nbsp;".' '.$locations->location_name.'</option>';
                    }
                }
            }*/
            
            
            $this->view->data['locations'] = $district;

            $manifest_model = $this->model->get('manifestModel');
            $mani = $manifest_model->getAllManifest();
            $mani_arr = array();
            foreach ($mani as $manifest) {
                $mani_arr[$manifest->manifest_type][] = $manifest;
            }
            $manifest_1 = $mani_arr[1];
            $manifest_2 = $mani_arr[2];
            
            $this->view->data['type_1'] = $manifest_1;
            $this->view->data['type_2'] = $manifest_2;

            $port_model = $this->model->get('portModel');
            $port = $port_model->getAllPort(array('order_by'=>'district ASC, port_name ','order'=>'ASC'));

            $this->view->data['ports'] = $port;
            
            $this->view->show('quotation/index');
        

        
        
    }

    public function getTransport(){
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
            $normal = isset($_POST['normal']) ? $_POST['normal'] : null;
            $special = isset($_POST['special']) ? $_POST['special'] : null;
            $tons = isset($_POST['tons']) ? $_POST['tons'] : 0;
            $soluong = isset($_POST['soluong']) ? $_POST['soluong'] : 0;
            $sotan = isset($_POST['sotan']) ? $_POST['sotan'] : 0;
            $fix = 1;
            $opt = isset($_SESSION['userid_logined'])?0:100000;

            $quatai = isset($_POST['quatai']) ? $_POST['quatai'] : null;


            $transport_model = $this->model->get('transportModel');
            
            

            if ($loc_from != '' && $loc_to != '' ) {
                
                $trans_from = 0;
                $trans_to = 0;

                if ($loc_from == $loc_to) {
                    
                    $data = array(
                        'bo' => 0,
                        'thuy' => 0,
                        'err' => null,
                        );
                    echo json_encode($data);
                    //return false;
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
                                'bo' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1),
                                'thuy' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1),
                                'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                'from' => $loc_from,
                                'to' => $loc_to,
                                );
                            echo json_encode($data);
                            //return false;
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                $data = array(
                                    'bo' => (($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    );
                                echo json_encode($data);
                                //return false;
                            }
                        }
                        else{
                            $data = array(
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        'from' => $loc_from,
                                        'to' => $loc_to,
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
                                    'bo' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1),
                                    'thuy' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1),
                                    'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    );
                            //var_dump($trans_to);die();
                                echo json_encode($data);
                            //return false;
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->c40_feet)*$soluong*$fix*($sotan>30?5:1);
                                $data = array(
                                    'bo' => (($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    );
                                echo json_encode($data);
                                //return false;
                            }
                        }
                        else{
                            $data = array(
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        );
                                    echo json_encode($data);
                                //return false;
                        }
                    }
                    else if($normal == 45){
                        $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_from.' AND loc_to = '.$loc_to);
                        if ($transports == null) {
                            $transports = $transport_model->getTransportByField('c40_feet','loc_from = '.$loc_to.' AND loc_to = '.$loc_from);
                        }
                        if ($transports == null) {
                            
                            //echo ($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1) ;
                            $data = array(
                                    'bo' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1),
                                    'thuy' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*$fix*($sotan>30?5:1),
                                    'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    );
                                echo json_encode($data);
                            //return false;
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+($transport->c40_feet+300000))*$soluong*$fix*($sotan>30?5:1);
                                $data = array(
                                    'bo' => (($sotan<=20?($transport->c40_feet+300000)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c40_feet+300000)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c40_feet+300000)*2:$transport->c40_feet+300000))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?($transport->c40_feet+300000)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c40_feet+300000)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c40_feet+300000)*2:$transport->c40_feet+300000))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    );
                                echo json_encode($data);
                                //return false;
                            }
                        }
                        else{
                            $data = array(
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        'from' => $loc_from,
                                        'to' => $loc_to,
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
                                    'bo' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong,
                                    'thuy' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong,
                                    'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    );
                                echo json_encode($data);
                            //return false;
                        }
                        else if($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->c20_ton)*$soluong;
                                $data = array(
                                        'bo' => ($transport->c40_feet-200000)*$soluong+$opt,
                                        'thuy' => ($transport->c40_feet-200000)*$soluong+$opt,
                                        'err' => null,
                                        'from' => $loc_from,
                                    'to' => $loc_to,
                                        );
                                    echo json_encode($data);
                                //return false;
                            }
                        }
                        else{
                            $data = array(
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        'from' => $loc_from,
                                        'to' => $loc_to,
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
                                    'bo' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong,
                                    'thuy' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong,
                                    'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    );
                            //var_dump('dsdsd');
                                echo json_encode($data);
                            //return false;
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->c28_ton)*$soluong;
                                $data = array(
                                        'bo' => ($transport->c40_feet)*$soluong+$opt,
                                        'thuy' => ($transport->c40_feet)*$soluong+$opt,
                                        'err' => null,
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        );
                                    echo json_encode($data);
                                //return false;
                            }
                        }
                        else{
                            $data = array(
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        'from' => $loc_from,
                                        'to' => $loc_to,
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
                                    'bo' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong,
                                    'thuy' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong,
                                    'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    );
                                echo json_encode($data);
                            //return false;
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->over_28_ton)*$soluong;
                                $data = array(
                                        'bo' => (round($transport->c40_feet/29*$tons))*$soluong+$opt,
                                        'thuy' => (round($transport->c40_feet/29*$tons))*$soluong+$opt,
                                        'err' => null,
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        );
                                    echo json_encode($data);
                                //return false;
                            }
                        }
                        else{
                            $data = array(
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        'from' => $loc_from,
                                        'to' => $loc_to,
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
                                    'bo' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*5,
                                    'thuy' => 0,//($opt+(($trans_from+$trans_to)/2 + 1000000))*$soluong*5,
                                    'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    );
                                echo json_encode($data);
                            //return false;
                        }
                        elseif($transports != null){
                            foreach ($transports as $transport) {
                                //echo ($opt+$transport->over_28_ton)*$soluong*5;
                                $data = array(
                                        'bo' => ($transport->c40_feet*2)*$soluong+$opt,
                                        'thuy' => ($transport->c40_feet*2)*$soluong+$opt,
                                        'err' => null,
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        );
                                    echo json_encode($data);
                                //return false;
                            }
                        }
                        else{
                            $data = array(
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        'from' => $loc_from,
                                        'to' => $loc_to,
                                        );
                                    echo json_encode($data);
                                //return false;
                        }
                        
                    }
                    else{
                        $data = array(
                                    'bo' => 0,
                                    'thuy' => 0,
                                    'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                    'from' => $loc_from,
                                    'to' => $loc_to,
                                    );
                                echo json_encode($data);
                        //return false;
                    }
                        
                }

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                
                
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


            if ($type_1 != null || $type_2 != null) {
                $manifest_model = $this->model->get('manifestModel');
                
                if ($type_1 != -1) {
                    $manifests = $manifest_model->getManifestByField('hcm,caimep','manifest_id = '.$type_1);
                    foreach ($manifests as $manifest) {
                        $data['hcm'] = $manifest->hcm*$sl;
                        $data['caimep'] = $manifest->caimep*$sl;
                    }
                    
                }
                else if ($type_2 != -1) {
                    $manifests = $manifest_model->getManifestByField('hcm,caimep','manifest_id = '.$type_2);
                    foreach ($manifests as $manifest) {
                        $data['hcm'] = $manifest->hcm*$sl;
                        $data['caimep'] = $manifest->caimep*$sl;
                    }
                    
                }
                echo json_encode($data);
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
            }
            
        }
    }

       

    public function view() {
        
        $this->view->show('quotation/view');
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
            $opt = isset($_SESSION['userid_logined'])?0:100000;

            $quatai = isset($_POST['quatai']) ? $_POST['quatai'] : null;

            if ($loc_from != null && $loc_to != null ) {
                $transport_model = $this->model->get('transportModel');
                $trans_from = 0;
                $trans_to = 0;

                if ($loc_from == $loc_to) {
                    
                    $data = array(
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
                                    'bo' => (($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    );
                                
                            }
                        }
                        else{
                            $data = array(
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
                                    'bo' => (($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    );
                                
                            }
                        }
                        else{
                            $data = array(
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        );
                                  
                        }
                    }
                    else if($normal == 45){
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
                                        'bo' => (500000+($sotan<=20?($transport->c40_feet+300000)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c40_feet+300000)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c40_feet+300000)*2:($transport->c40_feet+300000)))))*$soluong*$fix+$opt,
                                        'thuy' => (500000+($sotan<=20?($transport->c40_feet+300000)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c40_feet+300000)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c40_feet+300000)*2:($transport->c40_feet+300000)))))*$soluong*$fix+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            elseif  (($loc_to > 1 && $loc_to < 7) && $loc_from > 6)  {
                                $transports = $transport_model->getTransportByField('c40_feet','loc_from = 1 AND loc_to = '.$loc_from);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'bo' => (500000+($sotan<=20?($transport->c40_feet+300000)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c40_feet+300000)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c40_feet+300000)*2:($transport->c40_feet+300000)))))*$soluong*$fix+$opt,
                                        'thuy' => (500000+($sotan<=20?($transport->c40_feet+300000)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c40_feet+300000)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c40_feet+300000)*2:($transport->c40_feet+300000)))))*$soluong*$fix+$opt,
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
                                    'bo' => (($sotan<=20?($transport->c40_feet+300000)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c40_feet+300000)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c40_feet+300000)*2:$transport->c40_feet+300000))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?($transport->c40_feet+300000)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c40_feet+300000)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c40_feet+300000)*2:$transport->c40_feet+300000))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    );
                                
                            }
                        }
                        else{
                            $data = array(
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
                                        'bo' => ($transport->c40_feet-200000)*$soluong+$opt,
                                        'thuy' => ($transport->c40_feet-200000)*$soluong+$opt,
                                        'err' => null,
                                        );
                                   
                            }
                        }
                        else{
                            $data = array(
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
                                        'bo' => ($transport->c40_feet)*$soluong+$opt,
                                        'thuy' => ($transport->c40_feet)*$soluong+$opt,
                                        'err' => null,
                                        );
                                   
                            }
                        }
                        else{
                            $data = array(
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
                                        'bo' => (round($transport->c40_feet/29*$tons))*$soluong+$opt,
                                        'thuy' => (round($transport->c40_feet/29*$tons))*$soluong+$opt,
                                        'err' => null,
                                        );
                                  
                            }
                        }
                        else{
                            $data = array(
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
                                        'bo' => ($transport->c40_feet*2)*$soluong+$opt,
                                        'thuy' => ($transport->c40_feet*2)*$soluong+$opt,
                                        'err' => null,
                                        );
                                   
                            }
                        }
                        else{
                            $data = array(
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        );
                                   
                        }
                        
                    }
                    else{
                        $data = array(
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
               ->setCellValue('D14', $data['bo'])
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

    public function exportship(){
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $loc_from = isset($_POST['export_port_from']) ? $_POST['export_port_from'] : null;
            $loc_to = isset($_POST['export_port_to']) ? $_POST['export_port_to'] : null;
            

            if ($loc_from != null && $loc_to != null ) {
                $shipping_model = $this->model->get('shippingModel');
                

                if ($loc_from == $loc_to) {
                    
                    $data = array(
                        'gia' => 0,
                        );
                   
                }
                else{
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
                }
            }
                

            $district_model = $this->model->get('districtModel');
        $tuyendi = $district_model->getDistrict($loc_from)->district_name;
        $tuyenden = $district_model->getDistrict($loc_to)->district_name;
        if ($tuyendi=="TP Hải Phòng") {
            $tuyendi = "Hải Phòng";
        }
        else if ($tuyendi=="TP Đà Nẵng") {
            $tuyendi = "Đà Nẵng";
        }
        else if ($tuyendi=="Khánh Hòa") {
            $tuyendi = "Nha Trang";
        }
        else if ($tuyendi=="Bình Định") {
            $tuyendi = "Quy Nhơn";
        }

        if ($tuyenden=="TP Hải Phòng") {
            $tuyenden = "Hải Phòng";
        }
        else if ($tuyenden=="TP Đà Nẵng") {
            $tuyenden = "Đà Nẵng";
        }
        else if ($tuyenden=="Khánh Hòa") {
            $tuyenden = "Nha Trang";
        }
        else if ($tuyenden=="Bình Định") {
            $tuyenden = "Quy Nhơn";
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
               ->setCellValue('A10', 'Kính gửi quý khách hàng bảng báo giá dịch vụ vận chuyển container bằng đường thủy như sau:')
               ->setCellValue('A11', 'STT')
               ->setCellValue('B11', 'Cảng đi')
               ->setCellValue('C11', 'Cảng đến')
               ->setCellValue('D11', 'Tariff(VND)')
               ->setCellValue('D13', "20'")
               ->setCellValue('E13', "40'")
               ->setCellValue('A14', '1')
               ->setCellValue('B14', $tuyendi)
               ->setCellValue('C14', $tuyenden)
               ->setCellValue('D14', $data['shipping_c20'])
               ->setCellValue('E14', $data['shipping_c40'])
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
            $objPHPExcel->getActiveSheet()->mergeCells('A9:F9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:F10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:A13');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:B13');
            $objPHPExcel->getActiveSheet()->mergeCells('C11:C13');
            $objPHPExcel->getActiveSheet()->mergeCells('D11:E12');
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
            $objPHPExcel->getActiveSheet()->getStyle('E14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");

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
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
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
    public function exporttokhai(){
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $type = isset($_POST['export_import_export_type']) ? $_POST['export_import_export_type'] : null;
            $sl = isset($_POST['export_import_export']) ? $_POST['export_import_export'] : null;
            

            if ($type != null && $sl != null ) {
                if ($type == "import") {
                    $data['gia'] = $sl * 300000 + 400000;
                    $dv = "Thông quan hàng nhập khẩu";
                }
                else if ($type == "export") {
                    $data['gia'] = $sl * 400000 + 400000;
                    $dv = "Thông quan hàng xuất khẩu";
                }
            }
           
        
        require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");
            /*require ('lib/Classes/PHPExcel/Writer/PDF.php');
            require ('lib/Classes/PHPExcel/Writer/PDF/DomPDF.php');*/
            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A7', 'BẢNG BÁO GIÁ DỊCH VỤ MỞ TỜ KHAI')
               ->setCellValue('A9', 'Công ty Cai Mep Glogbal Logistics xin gửi lời cám ơn đến quý khách hàng đã quan tâm và sử dụng dịch vụ của chúng tôi.')
               ->setCellValue('A10', 'Kính gửi quý khách hàng bảng báo giá dịch vụ mở tờ khai như sau:')
               ->setCellValue('A11', 'STT')
               ->setCellValue('B11', 'DỊCH VỤ')
               ->setCellValue('E11', 'Tariff(VND)/'.$sl.' cont')
               ->setCellValue('A14', '1')
               ->setCellValue('B14', $dv)
               ->setCellValue('E14', $data['gia'])
               ->setCellValue('A16', 'Ghi chú:')
               ->setCellValue('A18', "1.")
               ->setCellValue('B18', "Giá không bao gồm thuế VAT 10%.")
               ->setCellValue('A19', "")
               ->setCellValue('B19', "")
               ->setCellValue('A20', "2.")
               ->setCellValue('B20', 'Chúng tôi sẵn sàng cung cấp dịch vụ 24/7.')
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
            $objPHPExcel->getActiveSheet()->getStyle('A11:F14')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A16')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A23')->getFont()->setItalic(true);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->mergeCells('A1:F5');
            $objPHPExcel->getActiveSheet()->mergeCells('A7:F7');
            $objPHPExcel->getActiveSheet()->mergeCells('A9:F9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:F10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:A13');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:D13');
            $objPHPExcel->getActiveSheet()->mergeCells('E11:F13');
            $objPHPExcel->getActiveSheet()->mergeCells('E14:F14');
            $objPHPExcel->getActiveSheet()->mergeCells('C11:C13');
            $objPHPExcel->getActiveSheet()->mergeCells('B14:D14');
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
            $objPHPExcel->getActiveSheet()->getStyle('E14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");

            $objPHPExcel->getActiveSheet()->getStyle('A1:F29')->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getStyle('A11:F14')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A11:F14')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
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
            $objPHPExcel->getActiveSheet()->getStyle('A11:F14')->applyFromArray(
                array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => array('argb' => '000000'),
                        ),
                    ),
                    
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('A11:F13')->applyFromArray(
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
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('7')->setRowHeight(100);
            $objPHPExcel->getActiveSheet()->getRowDimension('8')->setRowHeight(27.75);
            $objPHPExcel->getActiveSheet()->getRowDimension('9')->setRowHeight(47);
            $objPHPExcel->getActiveSheet()->getRowDimension('10')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('15')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('13')->setRowHeight(30);
            $objPHPExcel->getActiveSheet()->getRowDimension('14')->setRowHeight(37.5);
            $objPHPExcel->getActiveSheet()->getRowDimension('17')->setRowHeight(25);
            $objPHPExcel->getActiveSheet()->getRowDimension('21')->setRowHeight(52.55);
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
    public function exportchuyencang(){
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $type = isset($_POST['export_chuyencang_type']) ? $_POST['export_chuyencang_type'] : null;
            $sl = isset($_POST['export_chuyencang']) ? $_POST['export_chuyencang'] : null;
            

            if ($type != null && $sl != null ) {
                if ($type == "bo") {
                    $data['gia'] = $sl * 110000 + 200000;
                    $dv = "Chuyển cảng đường bộ";
                }
                else if ($type == "salan") {
                    $data['gia'] = $sl * 80000 + 200000;
                    $dv = "Chuyển cảng sà lan";
                }
            }
           
        
        require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");
            /*require ('lib/Classes/PHPExcel/Writer/PDF.php');
            require ('lib/Classes/PHPExcel/Writer/PDF/DomPDF.php');*/
            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A7', 'BẢNG BÁO GIÁ DỊCH VỤ CHUYỂN CẢNG')
               ->setCellValue('A9', 'Công ty Cai Mep Glogbal Logistics xin gửi lời cám ơn đến quý khách hàng đã quan tâm và sử dụng dịch vụ của chúng tôi.')
               ->setCellValue('A10', 'Kính gửi quý khách hàng bảng báo giá dịch vụ chuyển cảng như sau:')
               ->setCellValue('A11', 'STT')
               ->setCellValue('B11', 'DỊCH VỤ')
               ->setCellValue('E11', 'Tariff(VND)/'.$sl.' cont')
               ->setCellValue('A14', '1')
               ->setCellValue('B14', $dv)
               ->setCellValue('E14', $data['gia'])
               ->setCellValue('A16', 'Ghi chú:')
               ->setCellValue('A18', "1.")
               ->setCellValue('B18', "Giá không bao gồm thuế VAT 10%.")
               ->setCellValue('A19', "")
               ->setCellValue('B19', "")
               ->setCellValue('A20', "2.")
               ->setCellValue('B20', 'Chúng tôi sẵn sàng cung cấp dịch vụ 24/7.')
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
            $objPHPExcel->getActiveSheet()->getStyle('A11:F14')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A16')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A23')->getFont()->setItalic(true);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->mergeCells('A1:F5');
            $objPHPExcel->getActiveSheet()->mergeCells('A7:F7');
            $objPHPExcel->getActiveSheet()->mergeCells('A9:F9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:F10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:A13');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:D13');
            $objPHPExcel->getActiveSheet()->mergeCells('E11:F13');
            $objPHPExcel->getActiveSheet()->mergeCells('E14:F14');
            $objPHPExcel->getActiveSheet()->mergeCells('C11:C13');
            $objPHPExcel->getActiveSheet()->mergeCells('B14:D14');
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
            $objPHPExcel->getActiveSheet()->getStyle('E14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");

            $objPHPExcel->getActiveSheet()->getStyle('A1:F29')->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getStyle('A11:F14')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A11:F14')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
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
            $objPHPExcel->getActiveSheet()->getStyle('A11:F14')->applyFromArray(
                array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => array('argb' => '000000'),
                        ),
                    ),
                    
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('A11:F13')->applyFromArray(
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
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('7')->setRowHeight(100);
            $objPHPExcel->getActiveSheet()->getRowDimension('8')->setRowHeight(27.75);
            $objPHPExcel->getActiveSheet()->getRowDimension('9')->setRowHeight(47);
            $objPHPExcel->getActiveSheet()->getRowDimension('10')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('15')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('13')->setRowHeight(30);
            $objPHPExcel->getActiveSheet()->getRowDimension('14')->setRowHeight(37.5);
            $objPHPExcel->getActiveSheet()->getRowDimension('17')->setRowHeight(25);
            $objPHPExcel->getActiveSheet()->getRowDimension('21')->setRowHeight(52.55);
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
    public function exportmanifest(){
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $type1 = isset($_POST['export_type_1']) ? $_POST['export_type_1'] : null;
            $type2 = isset($_POST['export_type_2']) ? $_POST['export_type_2'] : null;
            $bill = isset($_POST['export_manifest']) ? $_POST['export_manifest'] : null;
            

            
            
            if ($type1 != "" && $type2 == "") {
                $manifest_model = $this->model->get('manifestModel');
                $manifests = $manifest_model->getManifestByField('manifest_case,hcm,caimep','manifest_id = '.$type1);
                if($manifests){
                    foreach ($manifests as $manifest) {
                        $data['hcm'] = $bill*$manifest->hcm;
                        $data['caimep'] = $bill*$manifest->caimep;
                        $data['case'] = $manifest->manifest_case;
                    }
                }
                else{
                    $data['hcm'] = 0;
                    $data['caimep'] = 0;
                    $data['case'] = "";
                }
            }
            else if ($type2 != "" && $type1 == "") {
                $manifest_model = $this->model->get('manifestModel');
                $manifests = $manifest_model->getManifestByField('manifest_case,hcm,caimep','manifest_id = '.$type2);
                if($manifests){
                    foreach ($manifests as $manifest) {
                        $data['hcm'] = $bill*$manifest->hcm;
                        $data['caimep'] = $bill*$manifest->caimep;
                        $data['case'] = $manifest->manifest_case;
                    }
                }
                else{
                    $data['hcm'] = 0;
                    $data['caimep'] = 0;
                    $data['case'] = $manifest->manifest_case;
                }
            }
        
        
        require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");
            /*require ('lib/Classes/PHPExcel/Writer/PDF.php');
            require ('lib/Classes/PHPExcel/Writer/PDF/DomPDF.php');*/
            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A7', 'BẢNG BÁO GIÁ DỊCH VỤ CHỈNH SỬA MANIFEST')
               ->setCellValue('A9', 'Công ty Cai Mep Glogbal Logistics xin gửi lời cám ơn đến quý khách hàng đã quan tâm và sử dụng dịch vụ của chúng tôi.')
               ->setCellValue('A10', 'Kính gửi quý khách hàng bảng báo giá dịch vụ chỉnh sửa manifest như sau:')
               ->setCellValue('A11', 'STT')
               ->setCellValue('B11', 'Dịch vụ')
               ->setCellValue('D11', 'Tariff(VND)/'.$bill.' bill')
               ->setCellValue('D13', "TP.HCM")
               ->setCellValue('E13', "Cái Mép")
               ->setCellValue('A14', '1')
               ->setCellValue('B14', $data['case'])
               ->setCellValue('D14', $data['hcm'])
               ->setCellValue('E14', $data['caimep'])
               ->setCellValue('A16', 'Ghi chú:')
               ->setCellValue('A17', "1.")
               ->setCellValue('B17', "Giá trên không bao gồm Thuế VAT 10%.")
               ->setCellValue('A18', "2.")
               ->setCellValue('B18', "Trong trường hợp đặc biệt (chỉnh mô tả hàng hóa) chúng ta sẽ thảo luận tùy trường hợp.")
               ->setCellValue('A19', "")
               ->setCellValue('B19', "")
               ->setCellValue('A20', "")
               ->setCellValue('B20', '')
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
            $objPHPExcel->getActiveSheet()->mergeCells('A9:F9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:F10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:A13');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:C13');
            $objPHPExcel->getActiveSheet()->mergeCells('B14:C14');
            $objPHPExcel->getActiveSheet()->mergeCells('D11:E12');
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
            $objPHPExcel->getActiveSheet()->getStyle('E14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");

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
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('7')->setRowHeight(100);
            $objPHPExcel->getActiveSheet()->getRowDimension('8')->setRowHeight(27.75);
            $objPHPExcel->getActiveSheet()->getRowDimension('9')->setRowHeight(47);
            $objPHPExcel->getActiveSheet()->getRowDimension('10')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('15')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('13')->setRowHeight(30);
            $objPHPExcel->getActiveSheet()->getRowDimension('14')->setRowHeight(37.5);
            $objPHPExcel->getActiveSheet()->getRowDimension('17')->setRowHeight(44.25);
            $objPHPExcel->getActiveSheet()->getRowDimension('19')->setRowHeight(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('21')->setRowHeight(52.55);
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
    public function exportthue(){
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $cont_type = isset($_POST['export_cont_type_rent']) ? $_POST['export_cont_type_rent'] : null;
            $day = isset($_POST['export_day_rent']) ? $_POST['export_day_rent'] : null;
            $sl = isset($_POST['export_cont_rent']) ? $_POST['export_cont_rent'] : null;

            if ($cont_type != null && $day != null && $sl != null) {

                if($cont_type == 'c20_dc'){
                    $name = "20' DC (Thường)";
                        if ($day <= 10) {
                            $tt = $day * $sl * 100000;
                            
                        }
                        else if ($day > 10 && $day <= 20) {
                            $tt = ((100000*10)+(100000*($day-10))*0.5)*$sl;
                            
                        }
                        else if ($day > 20) {
                            $tt = ((100000*10)+(100000*10)*0.5+(100000*($day-20))*0.25)*$sl;
                            
                        }
                    $size = "(D 6,0 m x R 2,4 m x C 2,5 m)";
                    $cont1 = "- Container kín nước, kín sáng, sàn chắc chắn, cửa dễ đóng mở, vỏ bằng thép.";
                     $cont2 = "- Ván sàn nguyên thủy, bằng gỗ bền chắc và dễ sửa chữa thay thế.";
                     $cont3 = "- Nóc được che phủ bằng một bạt rời neo giữ bạt với vách container bằng những sợi dây cáp loại nhỏ, mềm.";
                     $cont4 = "";
                     $cont5 = "- Cửa đóng mở dễ dàng kín nước.";
                     $cont6 = "";
                     $cont7 = "- Sơn và vẽ logo theo yêu cầu của khách hàng.";
                     $cont8 = "";

                    }
                    else if($cont_type == 'c40_dc'){
                        $name = "40' DC (Thường)";
                        if ($day <= 10) {
                            $tt = $day * $sl * 300000;
                            
                        }
                        else if ($day > 10 && $day <= 20) {
                            $tt = ((300000*10)+(300000*($day-10))*0.5)*$sl;
                            
                        }
                        else if ($day > 20) {
                            $tt = ((300000*10)+(300000*10)*0.5+(300000*($day-20))*0.25)*$sl;
                            
                        }
                    $size = "(D 12,0 m x R 2,4 m x C 2,5 m)"; 
                    $cont1 = "- Container kín nước, kín sáng, sàn chắc chắn, cửa dễ đóng mở, vỏ bằng thép.";
                     $cont2 = "- Ván sàn nguyên thủy, bằng gỗ bền chắc và dễ sửa chữa thay thế.";
                     $cont3 = "- Nóc được che phủ bằng một bạt rời neo giữ bạt với vách container bằng những sợi dây cáp loại nhỏ, mềm.";
                     $cont4 = "";
                     $cont5 = "- Cửa đóng mở dễ dàng kín nước.";
                     $cont6 = "";
                     $cont7 = "- Sơn và vẽ logo theo yêu cầu của khách hàng.";
                     $cont8 = "";   

                    }
                    else if($cont_type == 'c20_ot'){
                        $name = "20' OT (Mở nắp)";
                        if ($day <= 10) {
                            $tt = $day * $sl * 200000;
                            
                        }
                        else if ($day > 10 && $day <= 20) {
                            $tt = ((200000*10)+(200000*($day-10))*0.5)*$sl;
                            
                        }
                        else if ($day > 20) {
                            $tt = ((200000*10)+(200000*10)*0.5+(200000*($day-20))*0.25)*$sl;
                            
                        }
                     $size = "(D 6,0 m x R 2,4 m x C 2,5 m)"; 
                     $cont1 = "- Sử dụng container bằng thép để chuyên chở các mặt hàng nông sản, hàng cao quá khổ.";
                     $cont2 = "- Ván sàn nguyên thủy, bằng gỗ bền chắc và dễ sửa chữa thay thế.";
                     $cont3 = "- Nóc được che phủ bằng một bạt rời neo giữ bạt với vách container bằng những sợi dây cáp loại nhỏ, mềm.";
                     $cont4 = "";
                     $cont5 = "- Gia công 01 cửa container nguyên thủy (Original) bên hông, 04 bửng đóng mở có khóa.";
                     $cont6 = "";
                     $cont7 = "- Sơn 1 lớp chống rỉ sét, 1 lớp phủ, 1 lớp sơn màu bên ngoài.";
                     $cont8 = "- Bar và bạt phủ đầy đủ.";  

                    }
                    else if($cont_type == 'c40_ot'){
                        $name = "40' OT (Mở nắp)";
                        if ($day <= 10) {
                            $tt = $day * $sl * 300000;
                            
                        }
                        else if ($day > 10 && $day <= 20) {
                            $tt = ((300000*10)+(300000*($day-10))*0.5)*$sl;
                            
                        }
                        else if ($day > 20) {
                            $tt = ((300000*10)+(300000*10)*0.5+(300000*($day-20))*0.25)*$sl;
                            
                        }
                     $size = "(D 12,0 m x R 2,4 m x C 2,5 m)";  
                     $cont1 = "- Sử dụng container bằng thép để chuyên chở các mặt hàng nông sản, hàng cao quá khổ.";
                     $cont2 = "- Ván sàn nguyên thủy, bằng gỗ bền chắc và dễ sửa chữa thay thế.";
                     $cont3 = "- Nóc được che phủ bằng một bạt rời neo giữ bạt với vách container bằng những sợi dây cáp loại nhỏ, mềm.";
                     $cont4 = "";
                     $cont5 = "- Gia công 01 cửa container nguyên thủy (Original) bên hông, 04 bửng đóng mở có khóa.";
                     $cont6 = "";
                     $cont7 = "- Sơn 1 lớp chống rỉ sét, 1 lớp phủ, 1 lớp sơn màu bên ngoài.";
                     $cont8 = "- Bar và bạt phủ đầy đủ.";   

                    }
                    else if($cont_type == 'c20_vf'){
                        $name = "20' VF (Văn phòng)";
                        if ($day <= 20) {
                            $tt = $day * $sl * 200000;
                        }
                        else if ($day > 20) {
                            $tt = ((200000*20)+(100000*($day-20)))*$sl;
                            
                        }
                     $size = "(D 6,0 m x R 2,4 m x C 2,5 m)";   
                     $cont1 = "- Sử dụng container bằng thép, xử lý kĩ thuật để làm văn phòng, nhà ở.";
                     $cont2 = "- Ván sàn container gỗ, trải simili PVC.";
                     $cont3 = "- Thiết kế khung gỗ (3cmx5cm) trong lót mốp cách nhiệt, bên ngoài ốp ván MDF.";
                     $cont4 = "- 04 cửa sổ nhôm (R1m x C0.8m), có lót kính và khung sắt bảo vệ, bên ngoài có mái che.";
                     $cont5 = "- 02 cửa panel nửa trên lắp kính (R0.8m x C2m), ổ khóa có tay nắm.";
                     $cont6 = "- Hệ thống dây dẫn điện âm tường.\n- 06 ổ cắm điện đôi, 04 bộ hộp đèn đơn 1m2, 04 công tắc đèn.\n- 02 aptomat máy lạnh, 01 aptomat nguồn.";
                     $cont7 = "- Sơn 1 lớp chống rỉ sét, 1 lớp phủ, 1 lớp sơn màu bên ngoài";
                     $cont8 = "- 02 quạt hút. 02 máy lạnh.";


                    }
                    else if($cont_type == 'c40_vf'){
                        $name = "40' VF (Văn phòng)";
                        if ($day <= 20) {
                            $tt = $day * $sl * 300000;
                        }
                        else if ($day > 20) {
                            $tt = ((300000*20)+(150000*($day-20)))*$sl;
                            
                        }
                      $size = "(D 12,0 m x R 2,4 m x C 2,5 m)";  
                      $cont1 = "- Sử dụng container bằng thép, xử lý kĩ thuật để làm văn phòng, nhà ở.";
                     $cont2 = "- Ván sàn container gỗ, trải simili PVC.";
                     $cont3 = "- Thiết kế khung gỗ (3cmx5cm) trong lót mốp cách nhiệt, bên ngoài ốp ván MDF.";
                     $cont4 = "- 04 cửa sổ nhôm (R1m x C0.8m), có lót kính và khung sắt bảo vệ, bên ngoài có mái che.";
                     $cont5 = "- 02 cửa panel nửa trên lắp kính (R0.8m x C2m), ổ khóa có tay nắm.";
                     $cont6 = "- Hệ thống dây dẫn điện âm tường.\n- 06 ổ cắm điện đôi, 04 bộ hộp đèn đơn 1m2, 04 công tắc đèn.\n- 02 aptomat máy lạnh, 01 aptomat nguồn.";
                     $cont7 = "- Sơn 1 lớp chống rỉ sét, 1 lớp phủ, 1 lớp sơn màu bên ngoài";
                     $cont8 = "- 02 quạt hút. 02 máy lạnh.";

                    }
            }
                

        
        require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");
            /*require ('lib/Classes/PHPExcel/Writer/PDF.php');
            require ('lib/Classes/PHPExcel/Writer/PDF/DomPDF.php');*/
            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A7', 'BẢNG BÁO GIÁ DỊCH VỤ THUÊ CONTAINER')
               ->setCellValue('A9', 'Công ty Cai Mep Glogbal Logistics xin gửi lời cám ơn đến quý khách hàng đã quan tâm và sử dụng dịch vụ của chúng tôi.')
               ->setCellValue('A10', 'Kính gửi quý khách hàng bảng báo giá dịch vụ thuê container như sau:')
               ->setCellValue('A11', 'STT')
               ->setCellValue('B11', 'Loại container')
               ->setCellValue('C11', 'Số ngày thuê')
               ->setCellValue('D11', 'Số lượng container')
               ->setCellValue('E11', 'Thành tiền')
               ->setCellValue('A14', '1')
               ->setCellValue('B14', $name)
               ->setCellValue('C14', $day)
               ->setCellValue('D14', $sl)
               ->setCellValue('E14', $tt)
               ->setCellValue('A16', 'QUY CÁCH LẮP ĐẶT')
               ->setCellValue('C16', "CONTAINER ".$name." MỚI 100% \n".$size)
               ->setCellValue('A17', 'CONTAINER')
               ->setCellValue('C17', $cont1)
               ->setCellValue('A18', 'SÀN')
               ->setCellValue('C18', $cont2)
               ->setCellValue('A19', 'TƯỜNG & TRẦN')
               ->setCellValue('C19', $cont3)
               ->setCellValue('A20', 'CỬA SỔ')
               ->setCellValue('C20', $cont4)
               ->setCellValue('A21', 'CỬA ĐI')
               ->setCellValue('C21', $cont5)
               ->setCellValue('A22', 'HỆ THỐNG ĐIỆN')
               ->setCellValue('C22', $cont6)
               ->setCellValue('A23', 'SƠN')
               ->setCellValue('C23', $cont7)
               ->setCellValue('A24', 'TRANG BỊ')
               ->setCellValue('C24', $cont8)
               ->setCellValue('A28', 'Ghi chú:')
               ->setCellValue('A29', "1.")
               ->setCellValue('B29', "Giá trên không bao gồm Thuế VAT 10%.")
               ->setCellValue('A30', "")
               ->setCellValue('B30', "")
               ->setCellValue('A31', "")
               ->setCellValue('B31', "")
               ->setCellValue('A32', "")
               ->setCellValue('B32', '')
               ->setCellValue('A33', '* Chúng tôi cam kết mang đến cho quý khách hàng chất lượng dịch vụ, đảm bảo thời gian giao hàng tại cảng đích với giá cả cạnh tranh. Chúng tôi thực hiện dịch vụ 24h/ngày, 7 ngày/tuần.')
               ->setCellValue('A35', 'Mong rằng CMG và quý khách hàng sẽ có cơ hội hợp tác lâu dài và ổn định.')
               ->setCellValue('A37', 'Trụ sở chính  : 29 Quốc lộ 51, Ấp Đồng, Xã Phước Tân, TP.Biên Hòa, T.Đồng Nai')
               ->setCellValue('A38', 'VPĐD           :  Số 5/20,  đường 24, phường Hiệp Bình Chánh, quận Thủ Đức, TP. Hồ Chí Minh. ')
               ->setCellValue('A39', 'Chi nhánh      : Cổng khu cảng Cái Mép Thượng, xã Tân Phước, huyện Tân Thành, tỉnh BR-VT')
               ->setCellValue('A40', 'Điện thoại     : 083.500.9000                                   Fax: 0613.937.677  ')
               ->setCellValue('A41', 'Email            : sale@cmglogistics.com.vn               Website: www.cmglogistics.com.vn - www.caimeptrading.com');

            

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

            $objPHPExcel->getActiveSheet()->getStyle("A1:F41")->getFont()->setName('Times New Roman');
            $objPHPExcel->getActiveSheet()->getStyle('A11:E14')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A28')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A35')->getFont()->setItalic(true);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->getStyle('A16:A24')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A16:C16')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->mergeCells('A16:B16');
            $objPHPExcel->getActiveSheet()->mergeCells('C16:E16');
            $objPHPExcel->getActiveSheet()->mergeCells('A17:B17');
            $objPHPExcel->getActiveSheet()->mergeCells('C17:E17');
            $objPHPExcel->getActiveSheet()->mergeCells('A18:B18');
            $objPHPExcel->getActiveSheet()->mergeCells('C18:E18');
            $objPHPExcel->getActiveSheet()->mergeCells('A19:B19');
            $objPHPExcel->getActiveSheet()->mergeCells('C19:E19');
            $objPHPExcel->getActiveSheet()->mergeCells('A20:B20');
            $objPHPExcel->getActiveSheet()->mergeCells('C20:E20');
            $objPHPExcel->getActiveSheet()->mergeCells('A21:B21');
            $objPHPExcel->getActiveSheet()->mergeCells('C21:E21');
            $objPHPExcel->getActiveSheet()->mergeCells('A22:B22');
            $objPHPExcel->getActiveSheet()->mergeCells('C22:E22');
            $objPHPExcel->getActiveSheet()->mergeCells('A23:B23');
            $objPHPExcel->getActiveSheet()->mergeCells('C23:E23');
            $objPHPExcel->getActiveSheet()->mergeCells('A24:B24');
            $objPHPExcel->getActiveSheet()->mergeCells('C24:E24');

            $objPHPExcel->getActiveSheet()->mergeCells('A1:F5');
            $objPHPExcel->getActiveSheet()->mergeCells('A7:F7');
            $objPHPExcel->getActiveSheet()->mergeCells('A9:F9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:F10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:A13');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:B13');
            $objPHPExcel->getActiveSheet()->mergeCells('C11:C13');
            $objPHPExcel->getActiveSheet()->mergeCells('D11:D13');
            $objPHPExcel->getActiveSheet()->mergeCells('E11:E13');
            $objPHPExcel->getActiveSheet()->mergeCells('A28:F28');
            $objPHPExcel->getActiveSheet()->mergeCells('B29:F29');
            $objPHPExcel->getActiveSheet()->mergeCells('B30:F30');
            $objPHPExcel->getActiveSheet()->mergeCells('B31:F31');
            $objPHPExcel->getActiveSheet()->mergeCells('B32:F32');
            $objPHPExcel->getActiveSheet()->mergeCells('A33:F33');
            $objPHPExcel->getActiveSheet()->mergeCells('A35:F35');
            $objPHPExcel->getActiveSheet()->mergeCells('A37:F37');
            $objPHPExcel->getActiveSheet()->mergeCells('A38:F38');
            $objPHPExcel->getActiveSheet()->mergeCells('A39:F39');
            $objPHPExcel->getActiveSheet()->mergeCells('A40:F40');
            $objPHPExcel->getActiveSheet()->mergeCells('A41:F41');

            $objPHPExcel->getActiveSheet()->getStyle("A7")->getFont()->setSize(20);
            $objPHPExcel->getActiveSheet()->getStyle("A9:F35")->getFont()->setSize(14);
            $objPHPExcel->getActiveSheet()->getStyle("A37:A41")->getFont()->setSize(14);
            $objPHPExcel->getActiveSheet()->getStyle("A28")->getFont()->setUnderline(PHPExcel_Style_Font::UNDERLINE_SINGLE);
            $objPHPExcel->getActiveSheet()->getStyle('D14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");
            $objPHPExcel->getActiveSheet()->getStyle('E14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");

            $objPHPExcel->getActiveSheet()->getStyle('A1:F41')->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getStyle('A11:E14')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A11:E14')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A35')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A35')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A29:A32')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objPHPExcel->getActiveSheet()->getStyle('A29:A32')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $objPHPExcel->getActiveSheet()->getStyle('B29:B32')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->getActiveSheet()->getStyle('B29:B32')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $objPHPExcel->getActiveSheet()->getStyle('A9')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $objPHPExcel->getActiveSheet()->getStyle('A10')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);

            $objPHPExcel->getActiveSheet()->getStyle('A16:A24')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A16:A24')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('C16')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('C16')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A1:F41')->applyFromArray(
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
            $objPHPExcel->getActiveSheet()->getStyle('A16:E24')->applyFromArray(
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
            $objPHPExcel->getActiveSheet()->getStyle('A37:F41')->applyFromArray(
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
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('7')->setRowHeight(100);
            $objPHPExcel->getActiveSheet()->getRowDimension('8')->setRowHeight(27.75);
            $objPHPExcel->getActiveSheet()->getRowDimension('9')->setRowHeight(31);
            $objPHPExcel->getActiveSheet()->getRowDimension('10')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('15')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('13')->setRowHeight(30);
            $objPHPExcel->getActiveSheet()->getRowDimension('14')->setRowHeight(37.5);
            $objPHPExcel->getActiveSheet()->getRowDimension('29')->setRowHeight(44.25);
            $objPHPExcel->getActiveSheet()->getRowDimension('30')->setRowHeight(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('31')->setRowHeight(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('32')->setRowHeight(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('33')->setRowHeight(52.55);
            $objPHPExcel->getActiveSheet()->getRowDimension('16')->setRowHeight(36.75);
            $objPHPExcel->getActiveSheet()->getRowDimension('20')->setRowHeight(38.25);
            $objPHPExcel->getActiveSheet()->getRowDimension('22')->setRowHeight(55.5);

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
    public function exportmua(){
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $cont_type = isset($_POST['export_cont_type_buy']) ? $_POST['export_cont_type_buy'] : null;
            $sl = isset($_POST['export_cont_buy']) ? $_POST['export_cont_buy'] : null;

            if ($cont_type != null && $sl != null) {

                if($cont_type == 'c20_dc'){
                    $name = "20' DC (Thường)";
                        $tt = $sl*25000000;
                      $size = "(D 6,0 m x R 2,4 m x C 2,5 m)";  
                      $cont1 = "- Container kín nước, kín sáng, sàn chắc chắn, cửa dễ đóng mở, vỏ bằng thép.";
                     $cont2 = "- Ván sàn nguyên thủy, bằng gỗ bền chắc và dễ sửa chữa thay thế.";
                     $cont3 = "- Nóc được che phủ bằng một bạt rời neo giữ bạt với vách container bằng những sợi dây cáp loại nhỏ, mềm.";
                     $cont4 = "";
                     $cont5 = "- Cửa đóng mở dễ dàng kín nước.";
                     $cont6 = "";
                     $cont7 = "- Sơn và vẽ logo theo yêu cầu của khách hàng.";
                     $cont8 = "";

                    }
                    else if($cont_type == 'c40_dc'){
                        $name = "40' DC (Thường)";
                        $tt = $sl*38000000;
                        $size = "(D 12,0 m x R 2,4 m x C 2,5 m)";
                        $cont1 = "- Container kín nước, kín sáng, sàn chắc chắn, cửa dễ đóng mở, vỏ bằng thép.";
                     $cont2 = "- Ván sàn nguyên thủy, bằng gỗ bền chắc và dễ sửa chữa thay thế.";
                     $cont3 = "- Nóc được che phủ bằng một bạt rời neo giữ bạt với vách container bằng những sợi dây cáp loại nhỏ, mềm.";
                     $cont4 = "";
                     $cont5 = "- Cửa đóng mở dễ dàng kín nước.";
                     $cont6 = "";
                     $cont7 = "- Sơn và vẽ logo theo yêu cầu của khách hàng.";
                     $cont8 = "";
                    }
                    else if($cont_type == 'c20_ot'){
                        $name = "20' OT (Mở nắp)";
                        $tt = $sl*25000000;
                     $size = "(D 6,0 m x R 2,4 m x C 2,5 m)";   
                     $cont1 = "- Sử dụng container bằng thép để chuyên chở các mặt hàng nông sản, hàng cao quá khổ.";
                     $cont2 = "- Ván sàn nguyên thủy, bằng gỗ bền chắc và dễ sửa chữa thay thế.";
                     $cont3 = "- Nóc được che phủ bằng một bạt rời neo giữ bạt với vách container bằng những sợi dây cáp loại nhỏ, mềm.";
                     $cont4 = "";
                     $cont5 = "- Gia công 01 cửa container nguyên thủy (Original) bên hông, 04 bửng đóng mở có khóa.";
                     $cont6 = "";
                     $cont7 = "- Sơn 1 lớp chống rỉ sét, 1 lớp phủ, 1 lớp sơn màu bên ngoài.";
                     $cont8 = "- Bar và bạt phủ đầy đủ.";   

                    }
                    else if($cont_type == 'c40_ot'){
                        $name = "40' OT (Mở nắp)";
                        $tt = $sl*38000000;
                       $size = "(D 12,0 m x R 2,4 m x C 2,5 m)"; 
                       $cont1 = "- Sử dụng container bằng thép để chuyên chở các mặt hàng nông sản, hàng cao quá khổ.";
                     $cont2 = "- Ván sàn nguyên thủy, bằng gỗ bền chắc và dễ sửa chữa thay thế.";
                     $cont3 = "- Nóc được che phủ bằng một bạt rời neo giữ bạt với vách container bằng những sợi dây cáp loại nhỏ, mềm.";
                     $cont4 = "";
                     $cont5 = "- Gia công 01 cửa container nguyên thủy (Original) bên hông, 04 bửng đóng mở có khóa.";
                     $cont6 = "";
                     $cont7 = "- Sơn 1 lớp chống rỉ sét, 1 lớp phủ, 1 lớp sơn màu bên ngoài.";
                     $cont8 = "- Bar và bạt phủ đầy đủ.";   

                    }
                    else if($cont_type == 'c20_vf'){
                        $name = "20' VF (Văn phòng)";
                        $tt = $sl*55000000;
                       $size = "(D 6,0 m x R 2,4 m x C 2,5 m)"; 
                       $cont1 = "- Sử dụng container bằng thép, xử lý kĩ thuật để làm văn phòng, nhà ở.";
                     $cont2 = "- Ván sàn container gỗ, trải simili PVC.";
                     $cont3 = "- Thiết kế khung gỗ (3cmx5cm) trong lót mốp cách nhiệt, bên ngoài ốp ván MDF.";
                     $cont4 = "- 04 cửa sổ nhôm (R1m x C0.8m), có lót kính và khung sắt bảo vệ, bên ngoài có mái che.";
                     $cont5 = "- 02 cửa panel nửa trên lắp kính (R0.8m x C2m), ổ khóa có tay nắm.";
                     $cont6 = "- Hệ thống dây dẫn điện âm tường.\n- 06 ổ cắm điện đôi, 04 bộ hộp đèn đơn 1m2, 04 công tắc đèn.\n- 02 aptomat máy lạnh, 01 aptomat nguồn.";
                     $cont7 = "- Sơn 1 lớp chống rỉ sét, 1 lớp phủ, 1 lớp sơn màu bên ngoài";
                     $cont8 = "- 02 quạt hút. 02 máy lạnh.";

                    }
                    else if($cont_type == 'c40_vf'){
                        $tt = $sl*95000000;
                        
                        $size = "(D 12,0 m x R 2,4 m x C 2,5 m)";
                        $cont1 = "- Sử dụng container bằng thép, xử lý kĩ thuật để làm văn phòng, nhà ở.";
                     $cont2 = "- Ván sàn container gỗ, trải simili PVC.";
                     $cont3 = "- Thiết kế khung gỗ (3cmx5cm) trong lót mốp cách nhiệt, bên ngoài ốp ván MDF.";
                     $cont4 = "- 04 cửa sổ nhôm (R1m x C0.8m), có lót kính và khung sắt bảo vệ, bên ngoài có mái che.";
                     $cont5 = "- 02 cửa panel nửa trên lắp kính (R0.8m x C2m), ổ khóa có tay nắm.";
                     $cont6 = "- Hệ thống dây dẫn điện âm tường.\n- 06 ổ cắm điện đôi, 04 bộ hộp đèn đơn 1m2, 04 công tắc đèn.\n- 02 aptomat máy lạnh, 01 aptomat nguồn.";
                     $cont7 = "- Sơn 1 lớp chống rỉ sét, 1 lớp phủ, 1 lớp sơn màu bên ngoài";
                     $cont8 = "- 02 quạt hút. 02 máy lạnh.";
                    }
            }
                

        
        require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");
            /*require ('lib/Classes/PHPExcel/Writer/PDF.php');
            require ('lib/Classes/PHPExcel/Writer/PDF/DomPDF.php');*/
            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A7', 'BẢNG BÁO GIÁ CONTAINER')
               ->setCellValue('A9', 'Công ty Cai Mep Glogbal Logistics xin gửi lời cám ơn đến quý khách hàng đã quan tâm và sử dụng dịch vụ của chúng tôi.')
               ->setCellValue('A10', 'Kính gửi quý khách hàng bảng báo giá container như sau:')
               ->setCellValue('A11', 'STT')
               ->setCellValue('B11', 'Loại container')
               ->setCellValue('D11', 'Số lượng container')
               ->setCellValue('E11', 'Thành tiền')
               ->setCellValue('A14', '1')
               ->setCellValue('B14', $name)
               ->setCellValue('D14', $sl)
               ->setCellValue('E14', $tt)
               ->setCellValue('A16', 'QUY CÁCH LẮP ĐẶT')
               ->setCellValue('C16', "CONTAINER ".$name." MỚI 100% \n".$size)
               ->setCellValue('A17', 'CONTAINER')
               ->setCellValue('C17', $cont1)
               ->setCellValue('A18', 'SÀN')
               ->setCellValue('C18', $cont2)
               ->setCellValue('A19', 'TƯỜNG & TRẦN')
               ->setCellValue('C19', $cont3)
               ->setCellValue('A20', 'CỬA SỔ')
               ->setCellValue('C20', $cont4)
               ->setCellValue('A21', 'CỬA ĐI')
               ->setCellValue('C21', $cont5)
               ->setCellValue('A22', 'HỆ THỐNG ĐIỆN')
               ->setCellValue('C22', $cont6)
               ->setCellValue('A23', 'SƠN')
               ->setCellValue('C23', $cont7)
               ->setCellValue('A24', 'TRANG BỊ')
               ->setCellValue('C24', $cont8)
               ->setCellValue('A28', 'Ghi chú:')
               ->setCellValue('A29', "1.")
               ->setCellValue('B29', "Giá trên không bao gồm Thuế VAT 10%.")
               ->setCellValue('A30', "")
               ->setCellValue('B30', "")
               ->setCellValue('A31', "")
               ->setCellValue('B31', "")
               ->setCellValue('A32', "")
               ->setCellValue('B32', '')
               ->setCellValue('A33', '* Chúng tôi cam kết mang đến cho quý khách hàng chất lượng dịch vụ, đảm bảo thời gian giao hàng tại cảng đích với giá cả cạnh tranh. Chúng tôi thực hiện dịch vụ 24h/ngày, 7 ngày/tuần.')
               ->setCellValue('A35', 'Mong rằng CMG và quý khách hàng sẽ có cơ hội hợp tác lâu dài và ổn định.')
               ->setCellValue('A37', 'Trụ sở chính  : 29 Quốc lộ 51, Ấp Đồng, Xã Phước Tân, TP.Biên Hòa, T.Đồng Nai')
               ->setCellValue('A38', 'VPĐD           :  Số 5/20,  đường 24, phường Hiệp Bình Chánh, quận Thủ Đức, TP. Hồ Chí Minh. ')
               ->setCellValue('A39', 'Chi nhánh      : Cổng khu cảng Cái Mép Thượng, xã Tân Phước, huyện Tân Thành, tỉnh BR-VT')
               ->setCellValue('A40', 'Điện thoại     : 083.500.9000                                   Fax: 0613.937.677  ')
               ->setCellValue('A41', 'Email            : sale@cmglogistics.com.vn               Website: www.cmglogistics.com.vn - www.caimeptrading.com');

            

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

            $objPHPExcel->getActiveSheet()->getStyle("A1:F41")->getFont()->setName('Times New Roman');
            $objPHPExcel->getActiveSheet()->getStyle('A11:E14')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A28')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A35')->getFont()->setItalic(true);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->getStyle('A16:A24')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A16:C16')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->mergeCells('A16:B16');
            $objPHPExcel->getActiveSheet()->mergeCells('C16:E16');
            $objPHPExcel->getActiveSheet()->mergeCells('A17:B17');
            $objPHPExcel->getActiveSheet()->mergeCells('C17:E17');
            $objPHPExcel->getActiveSheet()->mergeCells('A18:B18');
            $objPHPExcel->getActiveSheet()->mergeCells('C18:E18');
            $objPHPExcel->getActiveSheet()->mergeCells('A19:B19');
            $objPHPExcel->getActiveSheet()->mergeCells('C19:E19');
            $objPHPExcel->getActiveSheet()->mergeCells('A20:B20');
            $objPHPExcel->getActiveSheet()->mergeCells('C20:E20');
            $objPHPExcel->getActiveSheet()->mergeCells('A21:B21');
            $objPHPExcel->getActiveSheet()->mergeCells('C21:E21');
            $objPHPExcel->getActiveSheet()->mergeCells('A22:B22');
            $objPHPExcel->getActiveSheet()->mergeCells('C22:E22');
            $objPHPExcel->getActiveSheet()->mergeCells('A23:B23');
            $objPHPExcel->getActiveSheet()->mergeCells('C23:E23');
            $objPHPExcel->getActiveSheet()->mergeCells('A24:B24');
            $objPHPExcel->getActiveSheet()->mergeCells('C24:E24');

            $objPHPExcel->getActiveSheet()->mergeCells('A1:F5');
            $objPHPExcel->getActiveSheet()->mergeCells('A7:F7');
            $objPHPExcel->getActiveSheet()->mergeCells('A9:F9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:F10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:A13');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:C13');
            $objPHPExcel->getActiveSheet()->mergeCells('B14:C14');
            $objPHPExcel->getActiveSheet()->mergeCells('D11:D13');
            $objPHPExcel->getActiveSheet()->mergeCells('E11:E13');
            $objPHPExcel->getActiveSheet()->mergeCells('A28:F28');
            $objPHPExcel->getActiveSheet()->mergeCells('B29:F29');
            $objPHPExcel->getActiveSheet()->mergeCells('B30:F30');
            $objPHPExcel->getActiveSheet()->mergeCells('B31:F31');
            $objPHPExcel->getActiveSheet()->mergeCells('B32:F32');
            $objPHPExcel->getActiveSheet()->mergeCells('A33:F33');
            $objPHPExcel->getActiveSheet()->mergeCells('A35:F35');
            $objPHPExcel->getActiveSheet()->mergeCells('A37:F37');
            $objPHPExcel->getActiveSheet()->mergeCells('A38:F38');
            $objPHPExcel->getActiveSheet()->mergeCells('A39:F39');
            $objPHPExcel->getActiveSheet()->mergeCells('A40:F40');
            $objPHPExcel->getActiveSheet()->mergeCells('A41:F41');

            $objPHPExcel->getActiveSheet()->getStyle("A7")->getFont()->setSize(20);
            $objPHPExcel->getActiveSheet()->getStyle("A9:F35")->getFont()->setSize(14);
            $objPHPExcel->getActiveSheet()->getStyle("A37:A41")->getFont()->setSize(14);
            $objPHPExcel->getActiveSheet()->getStyle("A28")->getFont()->setUnderline(PHPExcel_Style_Font::UNDERLINE_SINGLE);
            $objPHPExcel->getActiveSheet()->getStyle('D14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");
            $objPHPExcel->getActiveSheet()->getStyle('E14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");

            $objPHPExcel->getActiveSheet()->getStyle('A1:F41')->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getStyle('A11:E14')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A11:E14')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A35')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A35')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A29:A32')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objPHPExcel->getActiveSheet()->getStyle('A29:A32')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $objPHPExcel->getActiveSheet()->getStyle('B29:B32')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->getActiveSheet()->getStyle('B29:B32')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $objPHPExcel->getActiveSheet()->getStyle('A9')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $objPHPExcel->getActiveSheet()->getStyle('A10')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $objPHPExcel->getActiveSheet()->getStyle('A16:A24')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A16:A24')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('C16')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('C16')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A1:F41')->applyFromArray(
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
            $objPHPExcel->getActiveSheet()->getStyle('A16:E24')->applyFromArray(
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
            $objPHPExcel->getActiveSheet()->getStyle('A37:F41')->applyFromArray(
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
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('7')->setRowHeight(100);
            $objPHPExcel->getActiveSheet()->getRowDimension('8')->setRowHeight(27.75);
            $objPHPExcel->getActiveSheet()->getRowDimension('9')->setRowHeight(31);
            $objPHPExcel->getActiveSheet()->getRowDimension('10')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('15')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('13')->setRowHeight(30);
            $objPHPExcel->getActiveSheet()->getRowDimension('14')->setRowHeight(37.5);
            $objPHPExcel->getActiveSheet()->getRowDimension('29')->setRowHeight(44.25);
            $objPHPExcel->getActiveSheet()->getRowDimension('30')->setRowHeight(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('31')->setRowHeight(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('32')->setRowHeight(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('33')->setRowHeight(52.55);
            $objPHPExcel->getActiveSheet()->getRowDimension('16')->setRowHeight(36.75);
            $objPHPExcel->getActiveSheet()->getRowDimension('20')->setRowHeight(38.25);
            $objPHPExcel->getActiveSheet()->getRowDimension('22')->setRowHeight(55.5);

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

    public function listdistrict(){
        $this->view->disableLayout();
        $district_model = $this->model->get('districtModel');
        $districts = $district_model->getAllDistrict();
        echo json_encode($districts);
    }
    public function listlocation(){
        $this->view->disableLayout();
        $location_model = $this->model->get('locationModel');
        $locations = $location_model->getAllLocation();
        echo json_encode($locations);
    }
    public function listmanifest(){
        $this->view->disableLayout();
        $manifest_model = $this->model->get('manifestModel');
        $manifests = $manifest_model->getAllManifest();
        echo json_encode($manifests);
    }
    public function listport(){
        $this->view->disableLayout();
        $port_model = $this->model->get('portModel');
        $ports = $port_model->getAllPort();
        echo json_encode($ports);
    }
    public function listshipping(){
        $district_model = $this->model->get('districtModel');
        $district_shipping = $district_model->getAllDistrict(array('where'=>'district_id in (SELECT loc_from FROM shipping) OR district_id in (SELECT loc_to FROM shipping)'));
        echo json_encode($district_shipping);
    }
    public function getlocationfrom(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $location_model = $this->model->get('locationModel');
            
            if ($_POST['keyword'] == "*") {

                $list = $location_model->getAllLocation(array('where'=>'district = '.$_POST['district']));
            }
            else{
                $data = array(
                'where'=>'( location_name LIKE "%'.$_POST['keyword'].'%" AND district = '.$_POST['district'].')',
                );
                $list = $location_model->getAllLocation($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $location_name = $rs->location_name;
                if ($_POST['keyword'] != "*") {
                    $location_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->location_name);
                }
                
                // add new option
                echo '<li onclick="set_item_location_from(\''.$rs->location_id.'\',\''.$rs->location_name.'\')">'.$location_name.'</li>';
            }
        }
    }
    public function getlocationto(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $location_model = $this->model->get('locationModel');
            
            if ($_POST['keyword'] == "*") {

                $list = $location_model->getAllLocation(array('where'=>'district = '.$_POST['district']));
            }
            else{
                $data = array(
                'where'=>'( location_name LIKE "%'.$_POST['keyword'].'%" AND district = '.$_POST['district'].')',
                );
                $list = $location_model->getAllLocation($data);
            }
            
            foreach ($list as $rs) {
                // put in bold the written text
                $location_name = $rs->location_name;
                if ($_POST['keyword'] != "*") {
                    $location_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->location_name);
                }
                
                // add new option
                echo '<li onclick="set_item_location_to(\''.$rs->location_id.'\',\''.$rs->location_name.'\')">'.$location_name.'</li>';
            }
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
    public function contract(){
        $this->view->data['title'] = 'Hợp đồng';
        
      if (isset($_COOKIE['cus'])) {
        $string = explode("-",$this->registry->router->addition);
        if ($string[0] == null) {
            return $this->view->redirect('quotation');
        }
        $loc_from = substr(base64_decode($string[0]),0,-1);
        $loc_to = substr(base64_decode($string[1]),0,-1);
        $soluong = substr(base64_decode($string[2]),0,-1);
        $sotan = substr(base64_decode($string[3]),0,-1);
        $loai = substr(base64_decode($string[4]),0,-1);
        $kichthuoc = substr(base64_decode($string[5]),0,-1);
        $tien = substr(base64_decode($string[6]),0,-2);


        if ($loc_from == null || $loc_to == null || $sotan == null || $soluong == null || $loai == null || $kichthuoc == null || $tien == null) {
            return $this->view->redirect('quotation');
        }
        if (!is_numeric($loc_from) || !is_numeric($loc_to) || !is_numeric($sotan) || !is_numeric($soluong) || !is_numeric($loai) || !is_numeric($kichthuoc) || !is_numeric($tien)) {
            return $this->view->redirect('quotation');
        }

        $location_model = $this->model->get('locationModel');
        
        $from = $location_model->getLocation($loc_from)->location_name;
        $to = $location_model->getLocation($loc_to)->location_name;

        if (!$from || !$to) {
            return $this->view->redirect('quotation');
        }
        if ($loai==1) {
            $type = 'DC (Thường)';
        }
        else if ($loai==2) {
            $type = 'HC (Cao)';
        }
        else if ($loai==3) {
            $type = 'RE (Lạnh)';
        }
        else if ($loai==4) {
            $type = 'HR (Lạnh, cao)';
        }
        else if ($loai==5) {
            $type = 'OT (Có thể mở nắp)';
        }
        else if ($loai==6) {
            $type = 'FR (Có thể mở nắp, mở cạnh)';
        }


        $this->view->data['from'] = $from;
        $this->view->data['to'] = $to;
        $this->view->data['sl'] = $soluong. ' chiếc';
        $this->view->data['ton'] = $sotan. ' tấn';
        $this->view->data['type'] = $type;
        $this->view->data['size'] = ($kichthuoc==220)?'2x20':$kichthuoc.' feet';
        $this->view->data['total'] = $this->lib->formatMoney($tien).' VNĐ';

        if (isset($_COOKIE['cus_email'])) {
          $e_customer = $this->model->get('ecustomerModel');
          $congty = $e_customer->getCustomerByWhere(array('e_customer_email'=>$_COOKIE['cus_email']));

          $this->view->data['id_customer'] = $congty->e_customer_id;
          $this->view->data['co_customer'] = $congty->e_customer_co;
          $this->view->data['email_customer'] = $congty->e_customer_email;
          $this->view->data['phone_customer'] = $congty->e_customer_phone;
          $this->view->data['add_customer'] = $congty->e_customer_address;
          $this->view->data['contact_customer'] = $congty->e_customer_contact;

          if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $contract_model = $this->model->get('econtractModel');

            $customer_data = array(
                'e_customer_co' => trim($_POST['name']),
                'e_customer_email' => trim($_POST['email']),
                'e_customer_phone' => trim($_POST['phone']),
                'e_customer_address' => trim($_POST['address']),
                'e_customer_contact' => trim($_POST['contact']),
                );

            $e_customer->updateCustomer($customer_data,array('e_customer_id'=>$congty->e_customer_id));

            $contract_data = array(
                'e_contract_date' => strtotime(date('d-m-Y')),
                'customer' => $congty->e_customer_id,
                'loc_from' => $loc_from,
                'loc_to' => $loc_to,
                'cont_size' => $kichthuoc,
                'cont_type' => $loai,
                'cont_numbers' => $soluong,
                'ton' => $sotan,
                'total' => $tien,
                );
            if (!$contract_model->getContractByWhere(array('e_contract_date'=>$contract_data['e_contract_date'],'customer'=>$contract_data['customer'],'loc_from'=>$contract_data['loc_from'],'loc_to'=>$contract_data['loc_to']))) {
                $contract_model->createContract($contract_data);
            }
            else{
                $contract_id = $contract_model->getContractByWhere(array('e_contract_date'=>$contract_data['e_contract_date'],'customer'=>$contract_data['customer'],'loc_from'=>$contract_data['loc_from'],'loc_to'=>$contract_data['loc_to']))->e_contract_id;
                $contract_model->updateContract($contract_data,array('e_contract_id'=>$contract_id));
            }
            
            $content_mail = array(
                'congty' => $customer_data['e_customer_co'],
                'sdt' => $customer_data['e_customer_phone'],
                'email' => $customer_data['e_customer_email'],
                'contact' => $customer_data['e_customer_contact'],
                'tuyenduong' => $from.' - '.$to,
                'loai' => $type,
                'soluong' => $soluong,
                'sotan' => $sotan,
                'feet' => ($kichthuoc==220)?'2x20':$kichthuoc.' feet',
            );

            $this->alertMail($content_mail);

            echo 'Thông tin của quý khách đã được tiếp nhận.';
            return true;
          }
        }
        else{
          if ($_SERVER['REQUEST_METHOD'] == 'POST') {

          }
        }

        $this->view->show('quotation/contract');
      }
    }

    public function suggest(){
        $this->view->data['title'] = 'Đề nghị giá';
        
      if (isset($_COOKIE['cus'])) {
        $string = explode("-",$this->registry->router->addition);
        if ($string[0] == null) {
            return $this->view->redirect('quotation');
        }
        $loc_from = substr(base64_decode($string[0]),0,-1);
        $loc_to = substr(base64_decode($string[1]),0,-1);
        $soluong = substr(base64_decode($string[2]),0,-1);
        $sotan = substr(base64_decode($string[3]),0,-1);
        $loai = substr(base64_decode($string[4]),0,-1);
        $kichthuoc = substr(base64_decode($string[5]),0,-1);
        $tien = substr(base64_decode($string[6]),0,-2);


        if ($loc_from == null || $loc_to == null || $sotan == null || $soluong == null || $loai == null || $kichthuoc == null || $tien == null) {
            return $this->view->redirect('quotation');
        }
        if (!is_numeric($loc_from) || !is_numeric($loc_to) || !is_numeric($sotan) || !is_numeric($soluong) || !is_numeric($loai) || !is_numeric($kichthuoc) || !is_numeric($tien)) {
            return $this->view->redirect('quotation');
        }

        $location_model = $this->model->get('locationModel');
        
        $from = $location_model->getLocation($loc_from)->location_name;
        $to = $location_model->getLocation($loc_to)->location_name;

        if (!$from || !$to) {
            return $this->view->redirect('quotation');
        }
        if ($loai==1) {
            $type = 'DC (Thường)';
        }
        else if ($loai==2) {
            $type = 'HC (Cao)';
        }
        else if ($loai==3) {
            $type = 'RE (Lạnh)';
        }
        else if ($loai==4) {
            $type = 'HR (Lạnh, cao)';
        }
        else if ($loai==5) {
            $type = 'OT (Có thể mở nắp)';
        }
        else if ($loai==6) {
            $type = 'FR (Có thể mở nắp, mở cạnh)';
        }

        $this->view->data['from'] = $from;
        $this->view->data['to'] = $to;
        $this->view->data['sl'] = $soluong. ' chiếc';
        $this->view->data['ton'] = $sotan. ' tấn';
        $this->view->data['type'] = $type;
        $this->view->data['size'] = $kichthuoc.' feet';
        $this->view->data['total'] = $this->lib->formatMoney($tien).' VNĐ';
        $this->view->data['price'] = $this->lib->formatMoney($tien);

        if (isset($_COOKIE['cus_email'])) {
          $e_customer = $this->model->get('ecustomerModel');
          $congty = $e_customer->getCustomerByWhere(array('e_customer_email'=>$_COOKIE['cus_email']));


          if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $suggest_model = $this->model->get('esuggestModel');

            $cus_fix = 1.15;
            
            if ($loai == 3 || $loai == 4 || $loai == 5 || $loai == 6) {
                $fix = 1.5;
            }
            else{
                $fix = 1;
            }

            if ($kichthuoc == 40 ) {
                $c40 = (trim(str_replace(',','',$_POST['price'])))/$cus_fix;
                if ($sotan <= 20) {
                    $c40 = (($c40+200000)/$soluong)/$fix;

                }
                elseif ($sotan >= 29) {
                    $c40 = (($c40/$sotan*29)/$soluong)/$fix;
                }
                else{
                    $c40 = ($c40/$soluong)/$fix;
                }

                //$c20 = $c40-300000;
                
            }
            else if ($kichthuoc == 20) {
                $c20 = (trim(str_replace(',','',$_POST['price'])))/$cus_fix;
                if ($sotan <= 20) {
                    $c20 = (($c20+200000)/$soluong)/$fix;

                }
                elseif ($sotan >= 29) {
                    $c20 = (($c20/$sotan*29)/$soluong)/$fix;
                }
                else{
                    $c20 = ($c20/$soluong)/$fix;
                }
                //$c40 = $c20+300000;
            }
            else if ($kichthuoc == 45) {
                $c45 = (trim(str_replace(',','',$_POST['price'])))/$cus_fix;
                if ($sotan <= 20) {
                    $c45 = (($c45+200000)/$soluong)/$fix;

                }
                elseif ($sotan >= 29) {
                    $c45 = (($c45/$sotan*29)/$soluong)/$fix;
                }
                else{
                    $c45 = ($c45/$soluong)/$fix;
                }

                //$c40 = $c45-300000;
                //$c20 = $c40-300000;
            }
            else if ($kichthuoc == 220) {
                $c2x20 = (trim(str_replace(',','',$_POST['price'])))/$cus_fix;
                if ($sotan <= 20) {
                    $c2x20 = (($c2x20+200000)/$soluong)/$fix;

                }
                elseif ($sotan >= 29) {
                    $c2x20 = (($c2x20/$sotan*29)/$soluong)/$fix;
                }
                else{
                    $c2x20 = ($c2x20/$soluong)/$fix;
                }

                //$c40 = $c45-300000;
                //$c20 = $c40-300000;
            }

            

            $suggest_data = array(
                'suggest_date' => strtotime(date('d-m-Y')),
                'customer' => $congty->e_customer_id,
                'loc_from' => $loc_from,
                'loc_to' => $loc_to,
                'c20_feet' => $c20,
                'c40_feet' => $c40,
                'c45_feet' => $c45,
                'c2x20_feet' => $c2x20,
                );
            $suggest_model->createSuggest($suggest_data);
           
            

            echo 'Thông tin của quý khách đã được tiếp nhận.';
            return true;
          }
        }
        else{
          if ($_SERVER['REQUEST_METHOD'] == 'POST') {

          }
        }

        $this->view->show('quotation/suggest');
      }
    }

    public function salesuggest(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 4 ) {
            return $this->view->redirect('user/login');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $loc_from = isset($_POST['loc_from'])?trim($_POST['loc_from']):null;
            $loc_to = isset($_POST['loc_to'])?trim($_POST['loc_to']):null;
            $soluong = isset($_POST['soluong'])?trim($_POST['soluong']):null;
            $sotan = isset($_POST['sotan'])?trim($_POST['sotan']):null;
            $tons = isset($_POST['tons'])?trim($_POST['tons']):null;
            $loai = isset($_POST['special'])?trim($_POST['special']):null;
            $kichthuoc = isset($_POST['normal'])?trim($_POST['normal']):null;

            if ($tons != null) {
                $sotan = $tons;
            }

            $suggest_model = $this->model->get('esuggestModel');

            
            
            if ($loai == 3 || $loai == 4 || $loai == 5 || $loai == 6) {
                $fix = 1.5;
            }
            else{
                $fix = 1;
            }

            if ($kichthuoc == 40 ) {
                $c40 = trim(str_replace(',','',$_POST['price']));
                if ($sotan <= 20) {
                    $c40 = (($c40+200000)/$soluong)/$fix;

                }
                elseif ($sotan >= 29) {
                    $c40 = (($c40/$sotan*29)/$soluong)/$fix;
                }
                else{
                    $c40 = ($c40/$soluong)/$fix;
                }

                //$c20 = $c40-300000;
                
            }
            else if ($kichthuoc == 20) {
                $c20 = trim(str_replace(',','',$_POST['price']));
                if ($sotan <= 20) {
                    $c20 = (($c20+200000)/$soluong)/$fix;

                }
                elseif ($sotan >= 29) {
                    $c20 = (($c20/$sotan*29)/$soluong)/$fix;
                }
                else{
                    $c20 = ($c20/$soluong)/$fix;
                }
                //$c40 = $c20+300000;
            }
            else if ($kichthuoc == 45) {
                $c45 = trim(str_replace(',','',$_POST['price']));
                if ($sotan <= 20) {
                    $c45 = (($c45+200000)/$soluong)/$fix;

                }
                elseif ($sotan >= 29) {
                    $c45 = (($c45/$sotan*29)/$soluong)/$fix;
                }
                else{
                    $c45 = ($c45/$soluong)/$fix;
                }

                //$c40 = $c45-300000;
                //$c20 = $c40-300000;
            }
            else if ($kichthuoc == 220) {
                $c2x20 = trim(str_replace(',','',$_POST['price']));
                if ($sotan <= 20) {
                    $c2x20 = (($c2x20+200000)/$soluong)/$fix;

                }
                elseif ($sotan >= 29) {
                    $c2x20 = (($c2x20/$sotan*29)/$soluong)/$fix;
                }
                else{
                    $c2x20 = ($c2x20/$soluong)/$fix;
                }

                //$c40 = $c45-300000;
                //$c20 = $c40-300000;
            }
            else if ($kichthuoc == null) {
                $c40 = trim(str_replace(',','',$_POST['price']));
                if ($sotan <= 20) {
                    $c40 = (($c40+200000)/$soluong)/$fix;

                }
                elseif ($sotan >= 29) {
                    $c40 = (($c40/$sotan*29)/$soluong)/$fix;
                }
                else{
                    $c40 = ($c40/$soluong)/$fix;
                }

                //$c20 = $c40-300000;
            }

            

            $suggest_data = array(
                'suggest_date' => strtotime(date('d-m-Y')),
                'sale' => $_SESSION['userid_logined'],
                'loc_from' => $loc_from,
                'loc_to' => $loc_to,
                'c20_feet' => $c20,
                'c40_feet' => $c40,
                'c45_feet' => $c45,
                'c2x20_feet' => $c2x20,
                );
            $suggest_model->createSuggest($suggest_data);
           
            

            echo 'Thông tin của bạn đã được tiếp nhận.';
            return true;
        }
    }

    public function exportTCMT(){
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
            $normal = isset($_POST['normal']) ? $_POST['normal'] : null;
            $special = isset($_POST['special']) ? $_POST['special'] : null;
            $tons = isset($_POST['tons']) ? $_POST['tons'] : 0;
            $soluong = isset($_POST['soluong']) ? $_POST['soluong'] : 0;
            $sotan = isset($_POST['sotan']) ? $_POST['sotan'] : 0;
            $fix = 1;
            $opt = isset($_SESSION['userid_logined'])?0:100000;

            $quatai = isset($_POST['quatai']) ? $_POST['quatai'] : null;

            if ($loc_from != null && $loc_to != null ) {
                $transport_model = $this->model->get('transportModel');
                $trans_from = 0;
                $trans_to = 0;

                if ($loc_from == $loc_to) {
                    
                    $data = array(
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
                                    'bo' => (($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?$transport->c20_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c20_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c20_feet*2:$transport->c20_feet))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    );
                                
                            }
                        }
                        else{
                            $data = array(
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
                                    'bo' => (($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?$transport->c40_feet-200000:(($sotan>=29 && $quatai==null)?round($transport->c40_feet/29*$sotan):(($sotan>=29 && $quatai==1)?$transport->c40_feet*2:$transport->c40_feet))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    );
                                
                            }
                        }
                        else{
                            $data = array(
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        );
                                  
                        }
                    }
                    else if($normal == 45){
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
                                        'bo' => (500000+($sotan<=20?($transport->c40_feet+300000)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c40_feet+300000)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c40_feet+300000)*2:($transport->c40_feet+300000)))))*$soluong*$fix+$opt,
                                        'thuy' => (500000+($sotan<=20?($transport->c40_feet+300000)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c40_feet+300000)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c40_feet+300000)*2:($transport->c40_feet+300000)))))*$soluong*$fix+$opt,
                                        'err' => null,
                                        );
                                    
                                }
                            }
                            elseif  (($loc_to > 1 && $loc_to < 7) && $loc_from > 6)  {
                                $transports = $transport_model->getTransportByField('c40_feet','loc_from = 1 AND loc_to = '.$loc_from);

                                foreach ($transports as $transport) {
                                    //echo ($opt+$transport->c20_feet)*$soluong*$fix*($sotan>30?5:1);
                                    $data = array(
                                        'bo' => (500000+($sotan<=20?($transport->c40_feet+300000)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c40_feet+300000)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c40_feet+300000)*2:($transport->c40_feet+300000)))))*$soluong*$fix+$opt,
                                        'thuy' => (500000+($sotan<=20?($transport->c40_feet+300000)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c40_feet+300000)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c40_feet+300000)*2:($transport->c40_feet+300000)))))*$soluong*$fix+$opt,
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
                                    'bo' => (($sotan<=20?($transport->c40_feet+300000)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c40_feet+300000)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c40_feet+300000)*2:$transport->c40_feet+300000))))*$soluong*$fix+$opt,
                                    'thuy' => (($sotan<=20?($transport->c40_feet+300000)-200000:(($sotan>=29 && $quatai==null)?round(($transport->c40_feet+300000)/29*$sotan):(($sotan>=29 && $quatai==1)?($transport->c40_feet+300000)*2:$transport->c40_feet+300000))))*$soluong*$fix+$opt,
                                    'err' => null,
                                    );
                                
                            }
                        }
                        else{
                            $data = array(
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
                                        'bo' => ($transport->c40_feet-200000)*$soluong+$opt,
                                        'thuy' => ($transport->c40_feet-200000)*$soluong+$opt,
                                        'err' => null,
                                        );
                                   
                            }
                        }
                        else{
                            $data = array(
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
                                        'bo' => ($transport->c40_feet)*$soluong+$opt,
                                        'thuy' => ($transport->c40_feet)*$soluong+$opt,
                                        'err' => null,
                                        );
                                   
                            }
                        }
                        else{
                            $data = array(
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
                                        'bo' => (round($transport->c40_feet/29*$tons))*$soluong+$opt,
                                        'thuy' => (round($transport->c40_feet/29*$tons))*$soluong+$opt,
                                        'err' => null,
                                        );
                                  
                            }
                        }
                        else{
                            $data = array(
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
                                        'bo' => ($transport->c40_feet*2)*$soluong+$opt,
                                        'thuy' => ($transport->c40_feet*2)*$soluong+$opt,
                                        'err' => null,
                                        );
                                   
                            }
                        }
                        else{
                            $data = array(
                                        'bo' => 0,
                                        'thuy' => 0,
                                        'err' => 'Giá cước tuyến đường này đang được cập nhật ! Hãy liên hệ với chúng tôi để được hỗ trợ.',
                                        );
                                   
                        }
                        
                    }
                    else{
                        $data = array(
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
               ->setCellValue('A9', 'Công ty CP Tân Cảng Miền Trung xin gửi lời cám ơn đến quý khách hàng đã quan tâm và sử dụng dịch vụ của chúng tôi.')
               ->setCellValue('A10', 'Kính gửi quý khách hàng bảng báo giá dịch vụ vận chuyển container bằng xe đầu kéo như sau:')
               ->setCellValue('A11', 'STT')
               ->setCellValue('B11', 'Tuyến dịch vụ')
               ->setCellValue('D11', 'Tariff(VND)/'.$soluong.' cont/'.$tan.' tấn')
               ->setCellValue('D13', $con_type)
               ->setCellValue('A14', '1')
               ->setCellValue('B14', $tuyendi)
               ->setCellValue('C14', $tuyenden)
               ->setCellValue('D14', $data['bo'])
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
               ->setCellValue('A23', 'Mong rằng TCMT và quý khách hàng sẽ có cơ hội hợp tác lâu dài và ổn định.')
               ->setCellValue('A25', 'Trụ sở chính  : Phường Hải Cảng, TP.Quy Nhơn, T.Bình Định')
               ->setCellValue('A26', 'Điện thoại     : 0932.6789.89                                   Fax: 0563.89.10.10  ')
               ->setCellValue('A27', 'Email            : biz@tancangmientrung.com               Website: www.tancangmientrung.com');

            

            $objRichText = new PHPExcel_RichText();
            $textBold = $objRichText->createTextRun("Tan Cang Mien Trung\n");
            $textBold->getFont()->getColor()->setARGB('022D55');
            $textBold->getFont()->setSize(20);
            $textBold->getFont()->setBold(true);
            $textBold->getFont()->setName('Times New Roman');

            $under = $objRichText->createTextRun('Top Connection ');
            $under->getFont()->getColor()->setARGB('FF0000');
            $under->getFont()->setSize(20);
            
            $under->getFont()->setBold(true);
            $under->getFont()->setName('Times New Roman');
            
            $nor = $objRichText->createTextRun('For Multimodal Transportation');
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

            $logo = "public/img/tcmt.png";
            $objDrawing->setPath($logo);
            $objDrawing->setHeight(96);  
            $objDrawing->setWidth(200);    
            $objDrawing->setCoordinates('B1');

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Tan Cang Mien Trung")
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

    public function exportshipTCMT(){
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
            $loc_from = isset($_POST['export_port_from']) ? $_POST['export_port_from'] : null;
            $loc_to = isset($_POST['export_port_to']) ? $_POST['export_port_to'] : null;
            

            if ($loc_from != null && $loc_to != null ) {
                $shipping_model = $this->model->get('shippingModel');
                

                if ($loc_from == $loc_to) {
                    
                    $data = array(
                        'gia' => 0,
                        );
                   
                }
                else{
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
                }
            }
                

            $district_model = $this->model->get('districtModel');
        $tuyendi = $district_model->getDistrict($loc_from)->district_name;
        $tuyenden = $district_model->getDistrict($loc_to)->district_name;
        if ($tuyendi=="TP Hải Phòng") {
            $tuyendi = "Hải Phòng";
        }
        else if ($tuyendi=="TP Đà Nẵng") {
            $tuyendi = "Đà Nẵng";
        }
        else if ($tuyendi=="Khánh Hòa") {
            $tuyendi = "Nha Trang";
        }
        else if ($tuyendi=="Bình Định") {
            $tuyendi = "Quy Nhơn";
        }

        if ($tuyenden=="TP Hải Phòng") {
            $tuyenden = "Hải Phòng";
        }
        else if ($tuyenden=="TP Đà Nẵng") {
            $tuyenden = "Đà Nẵng";
        }
        else if ($tuyenden=="Khánh Hòa") {
            $tuyenden = "Nha Trang";
        }
        else if ($tuyenden=="Bình Định") {
            $tuyenden = "Quy Nhơn";
        }
        
        require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");
            /*require ('lib/Classes/PHPExcel/Writer/PDF.php');
            require ('lib/Classes/PHPExcel/Writer/PDF/DomPDF.php');*/
            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A7', 'BẢNG BÁO GIÁ VẬN CHUYỂN')
               ->setCellValue('A9', 'Công ty CP Tân Cảng Miền Trung xin gửi lời cám ơn đến quý khách hàng đã quan tâm và sử dụng dịch vụ của chúng tôi.')
               ->setCellValue('A10', 'Kính gửi quý khách hàng bảng báo giá dịch vụ vận chuyển container bằng đường thủy như sau:')
               ->setCellValue('A11', 'STT')
               ->setCellValue('B11', 'Cảng đi')
               ->setCellValue('C11', 'Cảng đến')
               ->setCellValue('D11', 'Tariff(VND)')
               ->setCellValue('D13', "20'")
               ->setCellValue('E13', "40'")
               ->setCellValue('A14', '1')
               ->setCellValue('B14', $tuyendi)
               ->setCellValue('C14', $tuyenden)
               ->setCellValue('D14', $data['shipping_c20'])
               ->setCellValue('E14', $data['shipping_c40'])
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
               ->setCellValue('A23', 'Mong rằng TCMT và quý khách hàng sẽ có cơ hội hợp tác lâu dài và ổn định.')
               ->setCellValue('A25', 'Trụ sở chính  : Phường Hải Cảng, TP.Quy Nhơn, T.Bình Định')
               ->setCellValue('A26', 'Điện thoại     : 0932.6789.89                                   Fax: 0563.89.10.10  ')
               ->setCellValue('A27', 'Email            : biz@tancangmientrung.com               Website: www.tancangmientrung.com');

            

            $objRichText = new PHPExcel_RichText();
            $textBold = $objRichText->createTextRun("Tan Cang Mien Trung\n");
            $textBold->getFont()->getColor()->setARGB('022D55');
            $textBold->getFont()->setSize(20);
            $textBold->getFont()->setBold(true);
            $textBold->getFont()->setName('Times New Roman');

            $under = $objRichText->createTextRun('Top Connection ');
            $under->getFont()->getColor()->setARGB('FF0000');
            $under->getFont()->setSize(20);
            
            $under->getFont()->setBold(true);
            $under->getFont()->setName('Times New Roman');
            
            $nor = $objRichText->createTextRun('For Multimodal Transportation');
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

            $logo = "public/img/tcmt.png";
            $objDrawing->setPath($logo);
            $objDrawing->setHeight(96); 
            $objDrawing->setWidth(200);    
            $objDrawing->setCoordinates('B1');

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Tan Cang Mien Trung")
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
            $objPHPExcel->getActiveSheet()->mergeCells('A9:F9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:F10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:A13');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:B13');
            $objPHPExcel->getActiveSheet()->mergeCells('C11:C13');
            $objPHPExcel->getActiveSheet()->mergeCells('D11:E12');
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
            $objPHPExcel->getActiveSheet()->getStyle('E14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");

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
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
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
    public function exporttokhaiTCMT(){
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
            $type = isset($_POST['export_import_export_type']) ? $_POST['export_import_export_type'] : null;
            $sl = isset($_POST['export_import_export']) ? $_POST['export_import_export'] : null;
            

            if ($type != null && $sl != null ) {
                if ($type == "import") {
                    $data['gia'] = $sl * 300000 + 400000;
                    $dv = "Thông quan hàng nhập khẩu";
                }
                else if ($type == "export") {
                    $data['gia'] = $sl * 400000 + 400000;
                    $dv = "Thông quan hàng xuất khẩu";
                }
            }
           
        
        require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");
            /*require ('lib/Classes/PHPExcel/Writer/PDF.php');
            require ('lib/Classes/PHPExcel/Writer/PDF/DomPDF.php');*/
            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A7', 'BẢNG BÁO GIÁ DỊCH VỤ MỞ TỜ KHAI')
               ->setCellValue('A9', 'Công ty CP Tân Cảng Miền Trung xin gửi lời cám ơn đến quý khách hàng đã quan tâm và sử dụng dịch vụ của chúng tôi.')
               ->setCellValue('A10', 'Kính gửi quý khách hàng bảng báo giá dịch vụ mở tờ khai như sau:')
               ->setCellValue('A11', 'STT')
               ->setCellValue('B11', 'DỊCH VỤ')
               ->setCellValue('E11', 'Tariff(VND)/'.$sl.' cont')
               ->setCellValue('A14', '1')
               ->setCellValue('B14', $dv)
               ->setCellValue('E14', $data['gia'])
               ->setCellValue('A16', 'Ghi chú:')
               ->setCellValue('A18', "1.")
               ->setCellValue('B18', "Giá không bao gồm thuế VAT 10%.")
               ->setCellValue('A19', "")
               ->setCellValue('B19', "")
               ->setCellValue('A20', "2.")
               ->setCellValue('B20', 'Chúng tôi sẵn sàng cung cấp dịch vụ 24/7.')
               ->setCellValue('A21', '* Chúng tôi cam kết mang đến cho quý khách hàng chất lượng dịch vụ, đảm bảo thời gian giao hàng tại cảng đích với giá cả cạnh tranh. Chúng tôi thực hiện dịch vụ 24h/ngày, 7 ngày/tuần.')
               ->setCellValue('A23', 'Mong rằng TCMT và quý khách hàng sẽ có cơ hội hợp tác lâu dài và ổn định.')
               ->setCellValue('A25', 'Trụ sở chính  : Phường Hải Cảng, TP.Quy Nhơn, T.Bình Định')
               ->setCellValue('A26', 'Điện thoại     : 0932.6789.89                                   Fax: 0563.89.10.10  ')
               ->setCellValue('A27', 'Email            : biz@tancangmientrung.com               Website: www.tancangmientrung.com');

            

            $objRichText = new PHPExcel_RichText();
            $textBold = $objRichText->createTextRun("Tan Cang Mien Trung\n");
            $textBold->getFont()->getColor()->setARGB('022D55');
            $textBold->getFont()->setSize(20);
            $textBold->getFont()->setBold(true);
            $textBold->getFont()->setName('Times New Roman');

            $under = $objRichText->createTextRun('Top Connection ');
            $under->getFont()->getColor()->setARGB('FF0000');
            $under->getFont()->setSize(20);
            
            $under->getFont()->setBold(true);
            $under->getFont()->setName('Times New Roman');
            
            $nor = $objRichText->createTextRun('For Multimodal Transportation');
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

            $logo = "public/img/tcmt.png";
            $objDrawing->setPath($logo);
            $objDrawing->setHeight(96); 
            $objDrawing->setWidth(200);    
            $objDrawing->setCoordinates('B1');

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Tan Cang Mien Trung")
                            ->setLastModifiedBy('CMG')
                            ->setTitle("Quotation")
                            ->setSubject("Quotation")
                            ->setDescription("Quotation")
                            ->setKeywords("Quotation")
                            ->setCategory("Quotation");
            $objPHPExcel->getActiveSheet()->setTitle("Quotation");

            $objPHPExcel->getActiveSheet()->getStyle("A1:F29")->getFont()->setName('Times New Roman');
            $objPHPExcel->getActiveSheet()->getStyle('A11:F14')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A16')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A23')->getFont()->setItalic(true);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->mergeCells('A1:F5');
            $objPHPExcel->getActiveSheet()->mergeCells('A7:F7');
            $objPHPExcel->getActiveSheet()->mergeCells('A9:F9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:F10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:A13');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:D13');
            $objPHPExcel->getActiveSheet()->mergeCells('E11:F13');
            $objPHPExcel->getActiveSheet()->mergeCells('E14:F14');
            $objPHPExcel->getActiveSheet()->mergeCells('C11:C13');
            $objPHPExcel->getActiveSheet()->mergeCells('B14:D14');
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
            $objPHPExcel->getActiveSheet()->getStyle('E14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");

            $objPHPExcel->getActiveSheet()->getStyle('A1:F29')->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getStyle('A11:F14')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A11:F14')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
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
            $objPHPExcel->getActiveSheet()->getStyle('A11:F14')->applyFromArray(
                array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => array('argb' => '000000'),
                        ),
                    ),
                    
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('A11:F13')->applyFromArray(
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
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('7')->setRowHeight(100);
            $objPHPExcel->getActiveSheet()->getRowDimension('8')->setRowHeight(27.75);
            $objPHPExcel->getActiveSheet()->getRowDimension('9')->setRowHeight(47);
            $objPHPExcel->getActiveSheet()->getRowDimension('10')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('15')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('13')->setRowHeight(30);
            $objPHPExcel->getActiveSheet()->getRowDimension('14')->setRowHeight(37.5);
            $objPHPExcel->getActiveSheet()->getRowDimension('17')->setRowHeight(25);
            $objPHPExcel->getActiveSheet()->getRowDimension('21')->setRowHeight(52.55);
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
    public function exportchuyencangTCMT(){
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
            $type = isset($_POST['export_chuyencang_type']) ? $_POST['export_chuyencang_type'] : null;
            $sl = isset($_POST['export_chuyencang']) ? $_POST['export_chuyencang'] : null;
            

            if ($type != null && $sl != null ) {
                if ($type == "bo") {
                    $data['gia'] = $sl * 110000 + 200000;
                    $dv = "Chuyển cảng đường bộ";
                }
                else if ($type == "salan") {
                    $data['gia'] = $sl * 80000 + 200000;
                    $dv = "Chuyển cảng sà lan";
                }
            }
           
        
        require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");
            /*require ('lib/Classes/PHPExcel/Writer/PDF.php');
            require ('lib/Classes/PHPExcel/Writer/PDF/DomPDF.php');*/
            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A7', 'BẢNG BÁO GIÁ DỊCH VỤ CHUYỂN CẢNG')
               ->setCellValue('A9', 'Công ty CP Tân Cảng Miền Trung xin gửi lời cám ơn đến quý khách hàng đã quan tâm và sử dụng dịch vụ của chúng tôi.')
               ->setCellValue('A10', 'Kính gửi quý khách hàng bảng báo giá dịch vụ chuyển cảng như sau:')
               ->setCellValue('A11', 'STT')
               ->setCellValue('B11', 'DỊCH VỤ')
               ->setCellValue('E11', 'Tariff(VND)/'.$sl.' cont')
               ->setCellValue('A14', '1')
               ->setCellValue('B14', $dv)
               ->setCellValue('E14', $data['gia'])
               ->setCellValue('A16', 'Ghi chú:')
               ->setCellValue('A18', "1.")
               ->setCellValue('B18', "Giá không bao gồm thuế VAT 10%.")
               ->setCellValue('A19', "")
               ->setCellValue('B19', "")
               ->setCellValue('A20', "2.")
               ->setCellValue('B20', 'Chúng tôi sẵn sàng cung cấp dịch vụ 24/7.')
               ->setCellValue('A21', '* Chúng tôi cam kết mang đến cho quý khách hàng chất lượng dịch vụ, đảm bảo thời gian giao hàng tại cảng đích với giá cả cạnh tranh. Chúng tôi thực hiện dịch vụ 24h/ngày, 7 ngày/tuần.')
               ->setCellValue('A23', 'Mong rằng TCMT và quý khách hàng sẽ có cơ hội hợp tác lâu dài và ổn định.')
               ->setCellValue('A25', 'Trụ sở chính  : Phường Hải Cảng, TP.Quy Nhơn, T.Bình Định')
               ->setCellValue('A26', 'Điện thoại     : 0932.6789.89                                   Fax: 0563.89.10.10  ')
               ->setCellValue('A27', 'Email            : biz@tancangmientrung.com               Website: www.tancangmientrung.com');

            

            $objRichText = new PHPExcel_RichText();
            $textBold = $objRichText->createTextRun("Tan Cang Mien Trung\n");
            $textBold->getFont()->getColor()->setARGB('022D55');
            $textBold->getFont()->setSize(20);
            $textBold->getFont()->setBold(true);
            $textBold->getFont()->setName('Times New Roman');

            $under = $objRichText->createTextRun('Top Connection ');
            $under->getFont()->getColor()->setARGB('FF0000');
            $under->getFont()->setSize(20);
            
            $under->getFont()->setBold(true);
            $under->getFont()->setName('Times New Roman');
            
            $nor = $objRichText->createTextRun('For Multimodal Transportation');
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

            $logo = "public/img/tcmt.png";
            $objDrawing->setPath($logo);
            $objDrawing->setHeight(96);
            $objDrawing->setWidth(200);     
            $objDrawing->setCoordinates('B1');

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Tan Cang Mien Trung")
                            ->setLastModifiedBy('CMG')
                            ->setTitle("Quotation")
                            ->setSubject("Quotation")
                            ->setDescription("Quotation")
                            ->setKeywords("Quotation")
                            ->setCategory("Quotation");
            $objPHPExcel->getActiveSheet()->setTitle("Quotation");

            $objPHPExcel->getActiveSheet()->getStyle("A1:F29")->getFont()->setName('Times New Roman');
            $objPHPExcel->getActiveSheet()->getStyle('A11:F14')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A16')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A23')->getFont()->setItalic(true);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->mergeCells('A1:F5');
            $objPHPExcel->getActiveSheet()->mergeCells('A7:F7');
            $objPHPExcel->getActiveSheet()->mergeCells('A9:F9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:F10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:A13');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:D13');
            $objPHPExcel->getActiveSheet()->mergeCells('E11:F13');
            $objPHPExcel->getActiveSheet()->mergeCells('E14:F14');
            $objPHPExcel->getActiveSheet()->mergeCells('C11:C13');
            $objPHPExcel->getActiveSheet()->mergeCells('B14:D14');
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
            $objPHPExcel->getActiveSheet()->getStyle('E14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");

            $objPHPExcel->getActiveSheet()->getStyle('A1:F29')->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getStyle('A11:F14')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A11:F14')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
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
            $objPHPExcel->getActiveSheet()->getStyle('A11:F14')->applyFromArray(
                array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => array('argb' => '000000'),
                        ),
                    ),
                    
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('A11:F13')->applyFromArray(
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
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('7')->setRowHeight(100);
            $objPHPExcel->getActiveSheet()->getRowDimension('8')->setRowHeight(27.75);
            $objPHPExcel->getActiveSheet()->getRowDimension('9')->setRowHeight(47);
            $objPHPExcel->getActiveSheet()->getRowDimension('10')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('15')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('13')->setRowHeight(30);
            $objPHPExcel->getActiveSheet()->getRowDimension('14')->setRowHeight(37.5);
            $objPHPExcel->getActiveSheet()->getRowDimension('17')->setRowHeight(25);
            $objPHPExcel->getActiveSheet()->getRowDimension('21')->setRowHeight(52.55);
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
    public function exportmanifestTCMT(){
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
            $type1 = isset($_POST['export_type_1']) ? $_POST['export_type_1'] : null;
            $type2 = isset($_POST['export_type_2']) ? $_POST['export_type_2'] : null;
            $bill = isset($_POST['export_manifest']) ? $_POST['export_manifest'] : null;
            

            
            
            if ($type1 != "" && $type2 == "") {
                $manifest_model = $this->model->get('manifestModel');
                $manifests = $manifest_model->getManifestByField('manifest_case,hcm,caimep','manifest_id = '.$type1);
                if($manifests){
                    foreach ($manifests as $manifest) {
                        $data['hcm'] = $bill*$manifest->hcm;
                        $data['caimep'] = $bill*$manifest->caimep;
                        $data['case'] = $manifest->manifest_case;
                    }
                }
                else{
                    $data['hcm'] = 0;
                    $data['caimep'] = 0;
                    $data['case'] = "";
                }
            }
            else if ($type2 != "" && $type1 == "") {
                $manifest_model = $this->model->get('manifestModel');
                $manifests = $manifest_model->getManifestByField('manifest_case,hcm,caimep','manifest_id = '.$type2);
                if($manifests){
                    foreach ($manifests as $manifest) {
                        $data['hcm'] = $bill*$manifest->hcm;
                        $data['caimep'] = $bill*$manifest->caimep;
                        $data['case'] = $manifest->manifest_case;
                    }
                }
                else{
                    $data['hcm'] = 0;
                    $data['caimep'] = 0;
                    $data['case'] = $manifest->manifest_case;
                }
            }
        
        
        require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");
            /*require ('lib/Classes/PHPExcel/Writer/PDF.php');
            require ('lib/Classes/PHPExcel/Writer/PDF/DomPDF.php');*/
            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A7', 'BẢNG BÁO GIÁ DỊCH VỤ CHỈNH SỬA MANIFEST')
               ->setCellValue('A9', 'Công ty CP Tân Cảng Miền Trung xin gửi lời cám ơn đến quý khách hàng đã quan tâm và sử dụng dịch vụ của chúng tôi.')
               ->setCellValue('A10', 'Kính gửi quý khách hàng bảng báo giá dịch vụ chỉnh sửa manifest như sau:')
               ->setCellValue('A11', 'STT')
               ->setCellValue('B11', 'Dịch vụ')
               ->setCellValue('D11', 'Tariff(VND)/'.$bill.' bill')
               ->setCellValue('D13', "TP.HCM")
               ->setCellValue('E13', "Cái Mép")
               ->setCellValue('A14', '1')
               ->setCellValue('B14', $data['case'])
               ->setCellValue('D14', $data['hcm'])
               ->setCellValue('E14', $data['caimep'])
               ->setCellValue('A16', 'Ghi chú:')
               ->setCellValue('A17', "1.")
               ->setCellValue('B17', "Giá trên không bao gồm Thuế VAT 10%.")
               ->setCellValue('A18', "2.")
               ->setCellValue('B18', "Trong trường hợp đặc biệt (chỉnh mô tả hàng hóa) chúng ta sẽ thảo luận tùy trường hợp.")
               ->setCellValue('A19', "")
               ->setCellValue('B19', "")
               ->setCellValue('A20', "")
               ->setCellValue('B20', '')
               ->setCellValue('A21', '* Chúng tôi cam kết mang đến cho quý khách hàng chất lượng dịch vụ, đảm bảo thời gian giao hàng tại cảng đích với giá cả cạnh tranh. Chúng tôi thực hiện dịch vụ 24h/ngày, 7 ngày/tuần.')
               ->setCellValue('A23', 'Mong rằng TCMT và quý khách hàng sẽ có cơ hội hợp tác lâu dài và ổn định.')
               ->setCellValue('A25', 'Trụ sở chính  : Phường Hải Cảng, TP.Quy Nhơn, T.Bình Định')
               ->setCellValue('A26', 'Điện thoại     : 0932.6789.89                                   Fax: 0563.89.10.10  ')
               ->setCellValue('A27', 'Email            : biz@tancangmientrung.com               Website: www.tancangmientrung.com');

            

            $objRichText = new PHPExcel_RichText();
            $textBold = $objRichText->createTextRun("Tan Cang Mien Trung\n");
            $textBold->getFont()->getColor()->setARGB('022D55');
            $textBold->getFont()->setSize(20);
            $textBold->getFont()->setBold(true);
            $textBold->getFont()->setName('Times New Roman');

            $under = $objRichText->createTextRun('Top Connection ');
            $under->getFont()->getColor()->setARGB('FF0000');
            $under->getFont()->setSize(20);
            
            $under->getFont()->setBold(true);
            $under->getFont()->setName('Times New Roman');
            
            $nor = $objRichText->createTextRun('For Multimodal Transportation');
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

            $logo = "public/img/tcmt.png";
            $objDrawing->setPath($logo);
            $objDrawing->setHeight(96);
            $objDrawing->setWidth(200);     
            $objDrawing->setCoordinates('B1');

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Tan Cang Mien Trung")
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
            $objPHPExcel->getActiveSheet()->mergeCells('A9:F9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:F10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:A13');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:C13');
            $objPHPExcel->getActiveSheet()->mergeCells('B14:C14');
            $objPHPExcel->getActiveSheet()->mergeCells('D11:E12');
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
            $objPHPExcel->getActiveSheet()->getStyle('E14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");

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
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('7')->setRowHeight(100);
            $objPHPExcel->getActiveSheet()->getRowDimension('8')->setRowHeight(27.75);
            $objPHPExcel->getActiveSheet()->getRowDimension('9')->setRowHeight(47);
            $objPHPExcel->getActiveSheet()->getRowDimension('10')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('15')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('13')->setRowHeight(30);
            $objPHPExcel->getActiveSheet()->getRowDimension('14')->setRowHeight(37.5);
            $objPHPExcel->getActiveSheet()->getRowDimension('17')->setRowHeight(44.25);
            $objPHPExcel->getActiveSheet()->getRowDimension('19')->setRowHeight(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('21')->setRowHeight(52.55);
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
    public function exportthueTCMT(){
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
            $cont_type = isset($_POST['export_cont_type_rent']) ? $_POST['export_cont_type_rent'] : null;
            $day = isset($_POST['export_day_rent']) ? $_POST['export_day_rent'] : null;
            $sl = isset($_POST['export_cont_rent']) ? $_POST['export_cont_rent'] : null;

            if ($cont_type != null && $day != null && $sl != null) {

                if($cont_type == 'c20_dc'){
                    $name = "20' DC (Thường)";
                        if ($day <= 10) {
                            $tt = $day * $sl * 100000;
                            
                        }
                        else if ($day > 10 && $day <= 20) {
                            $tt = ((100000*10)+(100000*($day-10))*0.5)*$sl;
                            
                        }
                        else if ($day > 20) {
                            $tt = ((100000*10)+(100000*10)*0.5+(100000*($day-20))*0.25)*$sl;
                            
                        }
                    $size = "(D 6,0 m x R 2,4 m x C 2,5 m)";
                    $cont1 = "- Container kín nước, kín sáng, sàn chắc chắn, cửa dễ đóng mở, vỏ bằng thép.";
                     $cont2 = "- Ván sàn nguyên thủy, bằng gỗ bền chắc và dễ sửa chữa thay thế.";
                     $cont3 = "- Nóc được che phủ bằng một bạt rời neo giữ bạt với vách container bằng những sợi dây cáp loại nhỏ, mềm.";
                     $cont4 = "";
                     $cont5 = "- Cửa đóng mở dễ dàng kín nước.";
                     $cont6 = "";
                     $cont7 = "- Sơn và vẽ logo theo yêu cầu của khách hàng.";
                     $cont8 = "";

                    }
                    else if($cont_type == 'c40_dc'){
                        $name = "40' DC (Thường)";
                        if ($day <= 10) {
                            $tt = $day * $sl * 300000;
                            
                        }
                        else if ($day > 10 && $day <= 20) {
                            $tt = ((300000*10)+(300000*($day-10))*0.5)*$sl;
                            
                        }
                        else if ($day > 20) {
                            $tt = ((300000*10)+(300000*10)*0.5+(300000*($day-20))*0.25)*$sl;
                            
                        }
                    $size = "(D 12,0 m x R 2,4 m x C 2,5 m)"; 
                    $cont1 = "- Container kín nước, kín sáng, sàn chắc chắn, cửa dễ đóng mở, vỏ bằng thép.";
                     $cont2 = "- Ván sàn nguyên thủy, bằng gỗ bền chắc và dễ sửa chữa thay thế.";
                     $cont3 = "- Nóc được che phủ bằng một bạt rời neo giữ bạt với vách container bằng những sợi dây cáp loại nhỏ, mềm.";
                     $cont4 = "";
                     $cont5 = "- Cửa đóng mở dễ dàng kín nước.";
                     $cont6 = "";
                     $cont7 = "- Sơn và vẽ logo theo yêu cầu của khách hàng.";
                     $cont8 = "";   

                    }
                    else if($cont_type == 'c20_ot'){
                        $name = "20' OT (Mở nắp)";
                        if ($day <= 10) {
                            $tt = $day * $sl * 200000;
                            
                        }
                        else if ($day > 10 && $day <= 20) {
                            $tt = ((200000*10)+(200000*($day-10))*0.5)*$sl;
                            
                        }
                        else if ($day > 20) {
                            $tt = ((200000*10)+(200000*10)*0.5+(200000*($day-20))*0.25)*$sl;
                            
                        }
                     $size = "(D 6,0 m x R 2,4 m x C 2,5 m)"; 
                     $cont1 = "- Sử dụng container bằng thép để chuyên chở các mặt hàng nông sản, hàng cao quá khổ.";
                     $cont2 = "- Ván sàn nguyên thủy, bằng gỗ bền chắc và dễ sửa chữa thay thế.";
                     $cont3 = "- Nóc được che phủ bằng một bạt rời neo giữ bạt với vách container bằng những sợi dây cáp loại nhỏ, mềm.";
                     $cont4 = "";
                     $cont5 = "- Gia công 01 cửa container nguyên thủy (Original) bên hông, 04 bửng đóng mở có khóa.";
                     $cont6 = "";
                     $cont7 = "- Sơn 1 lớp chống rỉ sét, 1 lớp phủ, 1 lớp sơn màu bên ngoài.";
                     $cont8 = "- Bar và bạt phủ đầy đủ.";  

                    }
                    else if($cont_type == 'c40_ot'){
                        $name = "40' OT (Mở nắp)";
                        if ($day <= 10) {
                            $tt = $day * $sl * 300000;
                            
                        }
                        else if ($day > 10 && $day <= 20) {
                            $tt = ((300000*10)+(300000*($day-10))*0.5)*$sl;
                            
                        }
                        else if ($day > 20) {
                            $tt = ((300000*10)+(300000*10)*0.5+(300000*($day-20))*0.25)*$sl;
                            
                        }
                     $size = "(D 12,0 m x R 2,4 m x C 2,5 m)";  
                     $cont1 = "- Sử dụng container bằng thép để chuyên chở các mặt hàng nông sản, hàng cao quá khổ.";
                     $cont2 = "- Ván sàn nguyên thủy, bằng gỗ bền chắc và dễ sửa chữa thay thế.";
                     $cont3 = "- Nóc được che phủ bằng một bạt rời neo giữ bạt với vách container bằng những sợi dây cáp loại nhỏ, mềm.";
                     $cont4 = "";
                     $cont5 = "- Gia công 01 cửa container nguyên thủy (Original) bên hông, 04 bửng đóng mở có khóa.";
                     $cont6 = "";
                     $cont7 = "- Sơn 1 lớp chống rỉ sét, 1 lớp phủ, 1 lớp sơn màu bên ngoài.";
                     $cont8 = "- Bar và bạt phủ đầy đủ.";   

                    }
                    else if($cont_type == 'c20_vf'){
                        $name = "20' VF (Văn phòng)";
                        if ($day <= 20) {
                            $tt = $day * $sl * 200000;
                        }
                        else if ($day > 20) {
                            $tt = ((200000*20)+(100000*($day-20)))*$sl;
                            
                        }
                     $size = "(D 6,0 m x R 2,4 m x C 2,5 m)";   
                     $cont1 = "- Sử dụng container bằng thép, xử lý kĩ thuật để làm văn phòng, nhà ở.";
                     $cont2 = "- Ván sàn container gỗ, trải simili PVC.";
                     $cont3 = "- Thiết kế khung gỗ (3cmx5cm) trong lót mốp cách nhiệt, bên ngoài ốp ván MDF.";
                     $cont4 = "- 04 cửa sổ nhôm (R1m x C0.8m), có lót kính và khung sắt bảo vệ, bên ngoài có mái che.";
                     $cont5 = "- 02 cửa panel nửa trên lắp kính (R0.8m x C2m), ổ khóa có tay nắm.";
                     $cont6 = "- Hệ thống dây dẫn điện âm tường.\n- 06 ổ cắm điện đôi, 04 bộ hộp đèn đơn 1m2, 04 công tắc đèn.\n- 02 aptomat máy lạnh, 01 aptomat nguồn.";
                     $cont7 = "- Sơn 1 lớp chống rỉ sét, 1 lớp phủ, 1 lớp sơn màu bên ngoài";
                     $cont8 = "- 02 quạt hút. 02 máy lạnh.";


                    }
                    else if($cont_type == 'c40_vf'){
                        $name = "40' VF (Văn phòng)";
                        if ($day <= 20) {
                            $tt = $day * $sl * 300000;
                        }
                        else if ($day > 20) {
                            $tt = ((300000*20)+(150000*($day-20)))*$sl;
                            
                        }
                      $size = "(D 12,0 m x R 2,4 m x C 2,5 m)";  
                      $cont1 = "- Sử dụng container bằng thép, xử lý kĩ thuật để làm văn phòng, nhà ở.";
                     $cont2 = "- Ván sàn container gỗ, trải simili PVC.";
                     $cont3 = "- Thiết kế khung gỗ (3cmx5cm) trong lót mốp cách nhiệt, bên ngoài ốp ván MDF.";
                     $cont4 = "- 04 cửa sổ nhôm (R1m x C0.8m), có lót kính và khung sắt bảo vệ, bên ngoài có mái che.";
                     $cont5 = "- 02 cửa panel nửa trên lắp kính (R0.8m x C2m), ổ khóa có tay nắm.";
                     $cont6 = "- Hệ thống dây dẫn điện âm tường.\n- 06 ổ cắm điện đôi, 04 bộ hộp đèn đơn 1m2, 04 công tắc đèn.\n- 02 aptomat máy lạnh, 01 aptomat nguồn.";
                     $cont7 = "- Sơn 1 lớp chống rỉ sét, 1 lớp phủ, 1 lớp sơn màu bên ngoài";
                     $cont8 = "- 02 quạt hút. 02 máy lạnh.";

                    }
            }
                

        
        require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");
            /*require ('lib/Classes/PHPExcel/Writer/PDF.php');
            require ('lib/Classes/PHPExcel/Writer/PDF/DomPDF.php');*/
            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A7', 'BẢNG BÁO GIÁ DỊCH VỤ THUÊ CONTAINER')
               ->setCellValue('A9', 'Công ty CP Tân Cảng Miền Trung xin gửi lời cám ơn đến quý khách hàng đã quan tâm và sử dụng dịch vụ của chúng tôi.')
               ->setCellValue('A10', 'Kính gửi quý khách hàng bảng báo giá dịch vụ thuê container như sau:')
               ->setCellValue('A11', 'STT')
               ->setCellValue('B11', 'Loại container')
               ->setCellValue('C11', 'Số ngày thuê')
               ->setCellValue('D11', 'Số lượng container')
               ->setCellValue('E11', 'Thành tiền')
               ->setCellValue('A14', '1')
               ->setCellValue('B14', $name)
               ->setCellValue('C14', $day)
               ->setCellValue('D14', $sl)
               ->setCellValue('E14', $tt)
               ->setCellValue('A16', 'QUY CÁCH LẮP ĐẶT')
               ->setCellValue('C16', "CONTAINER ".$name." MỚI 100% \n".$size)
               ->setCellValue('A17', 'CONTAINER')
               ->setCellValue('C17', $cont1)
               ->setCellValue('A18', 'SÀN')
               ->setCellValue('C18', $cont2)
               ->setCellValue('A19', 'TƯỜNG & TRẦN')
               ->setCellValue('C19', $cont3)
               ->setCellValue('A20', 'CỬA SỔ')
               ->setCellValue('C20', $cont4)
               ->setCellValue('A21', 'CỬA ĐI')
               ->setCellValue('C21', $cont5)
               ->setCellValue('A22', 'HỆ THỐNG ĐIỆN')
               ->setCellValue('C22', $cont6)
               ->setCellValue('A23', 'SƠN')
               ->setCellValue('C23', $cont7)
               ->setCellValue('A24', 'TRANG BỊ')
               ->setCellValue('C24', $cont8)
               ->setCellValue('A28', 'Ghi chú:')
               ->setCellValue('A29', "1.")
               ->setCellValue('B29', "Giá trên không bao gồm Thuế VAT 10%.")
               ->setCellValue('A30', "")
               ->setCellValue('B30', "")
               ->setCellValue('A31', "")
               ->setCellValue('B31', "")
               ->setCellValue('A32', "")
               ->setCellValue('B32', '')
               ->setCellValue('A33', '* Chúng tôi cam kết mang đến cho quý khách hàng chất lượng dịch vụ, đảm bảo thời gian giao hàng tại cảng đích với giá cả cạnh tranh. Chúng tôi thực hiện dịch vụ 24h/ngày, 7 ngày/tuần.')
               ->setCellValue('A35', 'Mong rằng TCMT và quý khách hàng sẽ có cơ hội hợp tác lâu dài và ổn định.')
               ->setCellValue('A37', 'Trụ sở chính  : Phường Hải Cảng, TP.Quy Nhơn, T.Bình Định')
               ->setCellValue('A38', 'Điện thoại     : 0932.6789.89                                   Fax: 0563.89.10.10  ')
               ->setCellValue('A39', 'Email            : biz@tancangmientrung.com               Website: www.tancangmientrung.com');

            

            $objRichText = new PHPExcel_RichText();
            $textBold = $objRichText->createTextRun("Tan Cang Mien Trung\n");
            $textBold->getFont()->getColor()->setARGB('022D55');
            $textBold->getFont()->setSize(20);
            $textBold->getFont()->setBold(true);
            $textBold->getFont()->setName('Times New Roman');

            $under = $objRichText->createTextRun('Top Connection ');
            $under->getFont()->getColor()->setARGB('FF0000');
            $under->getFont()->setSize(20);
            
            $under->getFont()->setBold(true);
            $under->getFont()->setName('Times New Roman');
            
            $nor = $objRichText->createTextRun('For Multimodal Transportation');
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

            $logo = "public/img/tcmt.png";
            $objDrawing->setPath($logo);
            $objDrawing->setHeight(96); 
            $objDrawing->setWidth(200);    
            $objDrawing->setCoordinates('B1');

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Tan Cang Mien Trung")
                            ->setLastModifiedBy('CMG')
                            ->setTitle("Quotation")
                            ->setSubject("Quotation")
                            ->setDescription("Quotation")
                            ->setKeywords("Quotation")
                            ->setCategory("Quotation");
            $objPHPExcel->getActiveSheet()->setTitle("Quotation");

            $objPHPExcel->getActiveSheet()->getStyle("A1:F41")->getFont()->setName('Times New Roman');
            $objPHPExcel->getActiveSheet()->getStyle('A11:E14')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A28')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A35')->getFont()->setItalic(true);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->getStyle('A16:A24')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A16:C16')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->mergeCells('A16:B16');
            $objPHPExcel->getActiveSheet()->mergeCells('C16:E16');
            $objPHPExcel->getActiveSheet()->mergeCells('A17:B17');
            $objPHPExcel->getActiveSheet()->mergeCells('C17:E17');
            $objPHPExcel->getActiveSheet()->mergeCells('A18:B18');
            $objPHPExcel->getActiveSheet()->mergeCells('C18:E18');
            $objPHPExcel->getActiveSheet()->mergeCells('A19:B19');
            $objPHPExcel->getActiveSheet()->mergeCells('C19:E19');
            $objPHPExcel->getActiveSheet()->mergeCells('A20:B20');
            $objPHPExcel->getActiveSheet()->mergeCells('C20:E20');
            $objPHPExcel->getActiveSheet()->mergeCells('A21:B21');
            $objPHPExcel->getActiveSheet()->mergeCells('C21:E21');
            $objPHPExcel->getActiveSheet()->mergeCells('A22:B22');
            $objPHPExcel->getActiveSheet()->mergeCells('C22:E22');
            $objPHPExcel->getActiveSheet()->mergeCells('A23:B23');
            $objPHPExcel->getActiveSheet()->mergeCells('C23:E23');
            $objPHPExcel->getActiveSheet()->mergeCells('A24:B24');
            $objPHPExcel->getActiveSheet()->mergeCells('C24:E24');

            $objPHPExcel->getActiveSheet()->mergeCells('A1:F5');
            $objPHPExcel->getActiveSheet()->mergeCells('A7:F7');
            $objPHPExcel->getActiveSheet()->mergeCells('A9:F9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:F10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:A13');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:B13');
            $objPHPExcel->getActiveSheet()->mergeCells('C11:C13');
            $objPHPExcel->getActiveSheet()->mergeCells('D11:D13');
            $objPHPExcel->getActiveSheet()->mergeCells('E11:E13');
            $objPHPExcel->getActiveSheet()->mergeCells('A28:F28');
            $objPHPExcel->getActiveSheet()->mergeCells('B29:F29');
            $objPHPExcel->getActiveSheet()->mergeCells('B30:F30');
            $objPHPExcel->getActiveSheet()->mergeCells('B31:F31');
            $objPHPExcel->getActiveSheet()->mergeCells('B32:F32');
            $objPHPExcel->getActiveSheet()->mergeCells('A33:F33');
            $objPHPExcel->getActiveSheet()->mergeCells('A35:F35');
            $objPHPExcel->getActiveSheet()->mergeCells('A37:F37');
            $objPHPExcel->getActiveSheet()->mergeCells('A38:F38');
            $objPHPExcel->getActiveSheet()->mergeCells('A39:F39');
            $objPHPExcel->getActiveSheet()->mergeCells('A40:F40');
            $objPHPExcel->getActiveSheet()->mergeCells('A41:F41');

            $objPHPExcel->getActiveSheet()->getStyle("A7")->getFont()->setSize(20);
            $objPHPExcel->getActiveSheet()->getStyle("A9:F35")->getFont()->setSize(14);
            $objPHPExcel->getActiveSheet()->getStyle("A37:A41")->getFont()->setSize(14);
            $objPHPExcel->getActiveSheet()->getStyle("A28")->getFont()->setUnderline(PHPExcel_Style_Font::UNDERLINE_SINGLE);
            $objPHPExcel->getActiveSheet()->getStyle('D14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");
            $objPHPExcel->getActiveSheet()->getStyle('E14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");

            $objPHPExcel->getActiveSheet()->getStyle('A1:F41')->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getStyle('A11:E14')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A11:E14')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A35')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A35')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A29:A32')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objPHPExcel->getActiveSheet()->getStyle('A29:A32')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $objPHPExcel->getActiveSheet()->getStyle('B29:B32')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->getActiveSheet()->getStyle('B29:B32')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $objPHPExcel->getActiveSheet()->getStyle('A9')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $objPHPExcel->getActiveSheet()->getStyle('A10')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);

            $objPHPExcel->getActiveSheet()->getStyle('A16:A24')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A16:A24')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('C16')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('C16')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A1:F41')->applyFromArray(
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
            $objPHPExcel->getActiveSheet()->getStyle('A16:E24')->applyFromArray(
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
            $objPHPExcel->getActiveSheet()->getStyle('A37:F41')->applyFromArray(
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
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('7')->setRowHeight(100);
            $objPHPExcel->getActiveSheet()->getRowDimension('8')->setRowHeight(27.75);
            $objPHPExcel->getActiveSheet()->getRowDimension('9')->setRowHeight(31);
            $objPHPExcel->getActiveSheet()->getRowDimension('10')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('15')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('13')->setRowHeight(30);
            $objPHPExcel->getActiveSheet()->getRowDimension('14')->setRowHeight(37.5);
            $objPHPExcel->getActiveSheet()->getRowDimension('29')->setRowHeight(44.25);
            $objPHPExcel->getActiveSheet()->getRowDimension('30')->setRowHeight(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('31')->setRowHeight(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('32')->setRowHeight(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('33')->setRowHeight(52.55);
            $objPHPExcel->getActiveSheet()->getRowDimension('16')->setRowHeight(36.75);
            $objPHPExcel->getActiveSheet()->getRowDimension('20')->setRowHeight(38.25);
            $objPHPExcel->getActiveSheet()->getRowDimension('22')->setRowHeight(55.5);

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
    public function exportmuaTCMT(){
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
            $cont_type = isset($_POST['export_cont_type_buy']) ? $_POST['export_cont_type_buy'] : null;
            $sl = isset($_POST['export_cont_buy']) ? $_POST['export_cont_buy'] : null;

            if ($cont_type != null && $sl != null) {

                if($cont_type == 'c20_dc'){
                    $name = "20' DC (Thường)";
                        $tt = $sl*25000000;
                      $size = "(D 6,0 m x R 2,4 m x C 2,5 m)";  
                      $cont1 = "- Container kín nước, kín sáng, sàn chắc chắn, cửa dễ đóng mở, vỏ bằng thép.";
                     $cont2 = "- Ván sàn nguyên thủy, bằng gỗ bền chắc và dễ sửa chữa thay thế.";
                     $cont3 = "- Nóc được che phủ bằng một bạt rời neo giữ bạt với vách container bằng những sợi dây cáp loại nhỏ, mềm.";
                     $cont4 = "";
                     $cont5 = "- Cửa đóng mở dễ dàng kín nước.";
                     $cont6 = "";
                     $cont7 = "- Sơn và vẽ logo theo yêu cầu của khách hàng.";
                     $cont8 = "";

                    }
                    else if($cont_type == 'c40_dc'){
                        $name = "40' DC (Thường)";
                        $tt = $sl*38000000;
                        $size = "(D 12,0 m x R 2,4 m x C 2,5 m)";
                        $cont1 = "- Container kín nước, kín sáng, sàn chắc chắn, cửa dễ đóng mở, vỏ bằng thép.";
                     $cont2 = "- Ván sàn nguyên thủy, bằng gỗ bền chắc và dễ sửa chữa thay thế.";
                     $cont3 = "- Nóc được che phủ bằng một bạt rời neo giữ bạt với vách container bằng những sợi dây cáp loại nhỏ, mềm.";
                     $cont4 = "";
                     $cont5 = "- Cửa đóng mở dễ dàng kín nước.";
                     $cont6 = "";
                     $cont7 = "- Sơn và vẽ logo theo yêu cầu của khách hàng.";
                     $cont8 = "";
                    }
                    else if($cont_type == 'c20_ot'){
                        $name = "20' OT (Mở nắp)";
                        $tt = $sl*25000000;
                     $size = "(D 6,0 m x R 2,4 m x C 2,5 m)";   
                     $cont1 = "- Sử dụng container bằng thép để chuyên chở các mặt hàng nông sản, hàng cao quá khổ.";
                     $cont2 = "- Ván sàn nguyên thủy, bằng gỗ bền chắc và dễ sửa chữa thay thế.";
                     $cont3 = "- Nóc được che phủ bằng một bạt rời neo giữ bạt với vách container bằng những sợi dây cáp loại nhỏ, mềm.";
                     $cont4 = "";
                     $cont5 = "- Gia công 01 cửa container nguyên thủy (Original) bên hông, 04 bửng đóng mở có khóa.";
                     $cont6 = "";
                     $cont7 = "- Sơn 1 lớp chống rỉ sét, 1 lớp phủ, 1 lớp sơn màu bên ngoài.";
                     $cont8 = "- Bar và bạt phủ đầy đủ.";   

                    }
                    else if($cont_type == 'c40_ot'){
                        $name = "40' OT (Mở nắp)";
                        $tt = $sl*38000000;
                       $size = "(D 12,0 m x R 2,4 m x C 2,5 m)"; 
                       $cont1 = "- Sử dụng container bằng thép để chuyên chở các mặt hàng nông sản, hàng cao quá khổ.";
                     $cont2 = "- Ván sàn nguyên thủy, bằng gỗ bền chắc và dễ sửa chữa thay thế.";
                     $cont3 = "- Nóc được che phủ bằng một bạt rời neo giữ bạt với vách container bằng những sợi dây cáp loại nhỏ, mềm.";
                     $cont4 = "";
                     $cont5 = "- Gia công 01 cửa container nguyên thủy (Original) bên hông, 04 bửng đóng mở có khóa.";
                     $cont6 = "";
                     $cont7 = "- Sơn 1 lớp chống rỉ sét, 1 lớp phủ, 1 lớp sơn màu bên ngoài.";
                     $cont8 = "- Bar và bạt phủ đầy đủ.";   

                    }
                    else if($cont_type == 'c20_vf'){
                        $name = "20' VF (Văn phòng)";
                        $tt = $sl*55000000;
                       $size = "(D 6,0 m x R 2,4 m x C 2,5 m)"; 
                       $cont1 = "- Sử dụng container bằng thép, xử lý kĩ thuật để làm văn phòng, nhà ở.";
                     $cont2 = "- Ván sàn container gỗ, trải simili PVC.";
                     $cont3 = "- Thiết kế khung gỗ (3cmx5cm) trong lót mốp cách nhiệt, bên ngoài ốp ván MDF.";
                     $cont4 = "- 04 cửa sổ nhôm (R1m x C0.8m), có lót kính và khung sắt bảo vệ, bên ngoài có mái che.";
                     $cont5 = "- 02 cửa panel nửa trên lắp kính (R0.8m x C2m), ổ khóa có tay nắm.";
                     $cont6 = "- Hệ thống dây dẫn điện âm tường.\n- 06 ổ cắm điện đôi, 04 bộ hộp đèn đơn 1m2, 04 công tắc đèn.\n- 02 aptomat máy lạnh, 01 aptomat nguồn.";
                     $cont7 = "- Sơn 1 lớp chống rỉ sét, 1 lớp phủ, 1 lớp sơn màu bên ngoài";
                     $cont8 = "- 02 quạt hút. 02 máy lạnh.";

                    }
                    else if($cont_type == 'c40_vf'){
                        $tt = $sl*95000000;
                        
                        $size = "(D 12,0 m x R 2,4 m x C 2,5 m)";
                        $cont1 = "- Sử dụng container bằng thép, xử lý kĩ thuật để làm văn phòng, nhà ở.";
                     $cont2 = "- Ván sàn container gỗ, trải simili PVC.";
                     $cont3 = "- Thiết kế khung gỗ (3cmx5cm) trong lót mốp cách nhiệt, bên ngoài ốp ván MDF.";
                     $cont4 = "- 04 cửa sổ nhôm (R1m x C0.8m), có lót kính và khung sắt bảo vệ, bên ngoài có mái che.";
                     $cont5 = "- 02 cửa panel nửa trên lắp kính (R0.8m x C2m), ổ khóa có tay nắm.";
                     $cont6 = "- Hệ thống dây dẫn điện âm tường.\n- 06 ổ cắm điện đôi, 04 bộ hộp đèn đơn 1m2, 04 công tắc đèn.\n- 02 aptomat máy lạnh, 01 aptomat nguồn.";
                     $cont7 = "- Sơn 1 lớp chống rỉ sét, 1 lớp phủ, 1 lớp sơn màu bên ngoài";
                     $cont8 = "- 02 quạt hút. 02 máy lạnh.";
                    }
            }
                

        
        require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");
            /*require ('lib/Classes/PHPExcel/Writer/PDF.php');
            require ('lib/Classes/PHPExcel/Writer/PDF/DomPDF.php');*/
            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A7', 'BẢNG BÁO GIÁ CONTAINER')
               ->setCellValue('A9', 'Công ty CP Tân Cảng Miền Trung xin gửi lời cám ơn đến quý khách hàng đã quan tâm và sử dụng dịch vụ của chúng tôi.')
               ->setCellValue('A10', 'Kính gửi quý khách hàng bảng báo giá container như sau:')
               ->setCellValue('A11', 'STT')
               ->setCellValue('B11', 'Loại container')
               ->setCellValue('D11', 'Số lượng container')
               ->setCellValue('E11', 'Thành tiền')
               ->setCellValue('A14', '1')
               ->setCellValue('B14', $name)
               ->setCellValue('D14', $sl)
               ->setCellValue('E14', $tt)
               ->setCellValue('A16', 'QUY CÁCH LẮP ĐẶT')
               ->setCellValue('C16', "CONTAINER ".$name." MỚI 100% \n".$size)
               ->setCellValue('A17', 'CONTAINER')
               ->setCellValue('C17', $cont1)
               ->setCellValue('A18', 'SÀN')
               ->setCellValue('C18', $cont2)
               ->setCellValue('A19', 'TƯỜNG & TRẦN')
               ->setCellValue('C19', $cont3)
               ->setCellValue('A20', 'CỬA SỔ')
               ->setCellValue('C20', $cont4)
               ->setCellValue('A21', 'CỬA ĐI')
               ->setCellValue('C21', $cont5)
               ->setCellValue('A22', 'HỆ THỐNG ĐIỆN')
               ->setCellValue('C22', $cont6)
               ->setCellValue('A23', 'SƠN')
               ->setCellValue('C23', $cont7)
               ->setCellValue('A24', 'TRANG BỊ')
               ->setCellValue('C24', $cont8)
               ->setCellValue('A28', 'Ghi chú:')
               ->setCellValue('A29', "1.")
               ->setCellValue('B29', "Giá trên không bao gồm Thuế VAT 10%.")
               ->setCellValue('A30', "")
               ->setCellValue('B30', "")
               ->setCellValue('A31', "")
               ->setCellValue('B31', "")
               ->setCellValue('A32', "")
               ->setCellValue('B32', '')
               ->setCellValue('A33', '* Chúng tôi cam kết mang đến cho quý khách hàng chất lượng dịch vụ, đảm bảo thời gian giao hàng tại cảng đích với giá cả cạnh tranh. Chúng tôi thực hiện dịch vụ 24h/ngày, 7 ngày/tuần.')
               ->setCellValue('A35', 'Mong rằng TCMT và quý khách hàng sẽ có cơ hội hợp tác lâu dài và ổn định.')
               ->setCellValue('A37', 'Trụ sở chính  : Phường Hải Cảng, TP.Quy Nhơn, T.Bình Định')
               ->setCellValue('A38', 'Điện thoại     : 0932.6789.89                                   Fax: 0563.89.10.10  ')
               ->setCellValue('A39', 'Email            : biz@tancangmientrung.com               Website: www.tancangmientrung.com');

            

            $objRichText = new PHPExcel_RichText();
            $textBold = $objRichText->createTextRun("Tan Cang Mien Trung\n");
            $textBold->getFont()->getColor()->setARGB('022D55');
            $textBold->getFont()->setSize(20);
            $textBold->getFont()->setBold(true);
            $textBold->getFont()->setName('Times New Roman');

            $under = $objRichText->createTextRun('Top Connection ');
            $under->getFont()->getColor()->setARGB('FF0000');
            $under->getFont()->setSize(20);
            
            $under->getFont()->setBold(true);
            $under->getFont()->setName('Times New Roman');
            
            $nor = $objRichText->createTextRun('For Multimodal Transportation');
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

            $logo = "public/img/tcmt.png";
            $objDrawing->setPath($logo);
            $objDrawing->setHeight(96);  
            $objDrawing->setWidth(200);   
            $objDrawing->setCoordinates('B1');

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Tan Cang Mien Trung")
                            ->setLastModifiedBy('CMG')
                            ->setTitle("Quotation")
                            ->setSubject("Quotation")
                            ->setDescription("Quotation")
                            ->setKeywords("Quotation")
                            ->setCategory("Quotation");
            $objPHPExcel->getActiveSheet()->setTitle("Quotation");

            $objPHPExcel->getActiveSheet()->getStyle("A1:F41")->getFont()->setName('Times New Roman');
            $objPHPExcel->getActiveSheet()->getStyle('A11:E14')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A28')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A35')->getFont()->setItalic(true);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->getStyle('A16:A24')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A16:C16')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->mergeCells('A16:B16');
            $objPHPExcel->getActiveSheet()->mergeCells('C16:E16');
            $objPHPExcel->getActiveSheet()->mergeCells('A17:B17');
            $objPHPExcel->getActiveSheet()->mergeCells('C17:E17');
            $objPHPExcel->getActiveSheet()->mergeCells('A18:B18');
            $objPHPExcel->getActiveSheet()->mergeCells('C18:E18');
            $objPHPExcel->getActiveSheet()->mergeCells('A19:B19');
            $objPHPExcel->getActiveSheet()->mergeCells('C19:E19');
            $objPHPExcel->getActiveSheet()->mergeCells('A20:B20');
            $objPHPExcel->getActiveSheet()->mergeCells('C20:E20');
            $objPHPExcel->getActiveSheet()->mergeCells('A21:B21');
            $objPHPExcel->getActiveSheet()->mergeCells('C21:E21');
            $objPHPExcel->getActiveSheet()->mergeCells('A22:B22');
            $objPHPExcel->getActiveSheet()->mergeCells('C22:E22');
            $objPHPExcel->getActiveSheet()->mergeCells('A23:B23');
            $objPHPExcel->getActiveSheet()->mergeCells('C23:E23');
            $objPHPExcel->getActiveSheet()->mergeCells('A24:B24');
            $objPHPExcel->getActiveSheet()->mergeCells('C24:E24');

            $objPHPExcel->getActiveSheet()->mergeCells('A1:F5');
            $objPHPExcel->getActiveSheet()->mergeCells('A7:F7');
            $objPHPExcel->getActiveSheet()->mergeCells('A9:F9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:F10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:A13');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:C13');
            $objPHPExcel->getActiveSheet()->mergeCells('B14:C14');
            $objPHPExcel->getActiveSheet()->mergeCells('D11:D13');
            $objPHPExcel->getActiveSheet()->mergeCells('E11:E13');
            $objPHPExcel->getActiveSheet()->mergeCells('A28:F28');
            $objPHPExcel->getActiveSheet()->mergeCells('B29:F29');
            $objPHPExcel->getActiveSheet()->mergeCells('B30:F30');
            $objPHPExcel->getActiveSheet()->mergeCells('B31:F31');
            $objPHPExcel->getActiveSheet()->mergeCells('B32:F32');
            $objPHPExcel->getActiveSheet()->mergeCells('A33:F33');
            $objPHPExcel->getActiveSheet()->mergeCells('A35:F35');
            $objPHPExcel->getActiveSheet()->mergeCells('A37:F37');
            $objPHPExcel->getActiveSheet()->mergeCells('A38:F38');
            $objPHPExcel->getActiveSheet()->mergeCells('A39:F39');
            $objPHPExcel->getActiveSheet()->mergeCells('A40:F40');
            $objPHPExcel->getActiveSheet()->mergeCells('A41:F41');

            $objPHPExcel->getActiveSheet()->getStyle("A7")->getFont()->setSize(20);
            $objPHPExcel->getActiveSheet()->getStyle("A9:F35")->getFont()->setSize(14);
            $objPHPExcel->getActiveSheet()->getStyle("A37:A41")->getFont()->setSize(14);
            $objPHPExcel->getActiveSheet()->getStyle("A28")->getFont()->setUnderline(PHPExcel_Style_Font::UNDERLINE_SINGLE);
            $objPHPExcel->getActiveSheet()->getStyle('D14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");
            $objPHPExcel->getActiveSheet()->getStyle('E14')->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");

            $objPHPExcel->getActiveSheet()->getStyle('A1:F41')->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getStyle('A11:E14')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A11:E14')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A35')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A35')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A29:A32')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objPHPExcel->getActiveSheet()->getStyle('A29:A32')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $objPHPExcel->getActiveSheet()->getStyle('B29:B32')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->getActiveSheet()->getStyle('B29:B32')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $objPHPExcel->getActiveSheet()->getStyle('A9')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $objPHPExcel->getActiveSheet()->getStyle('A10')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
            $objPHPExcel->getActiveSheet()->getStyle('A16:A24')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A16:A24')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('C16')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('C16')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A1:F41')->applyFromArray(
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
            $objPHPExcel->getActiveSheet()->getStyle('A16:E24')->applyFromArray(
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
            $objPHPExcel->getActiveSheet()->getStyle('A37:F41')->applyFromArray(
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
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(35);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('7')->setRowHeight(100);
            $objPHPExcel->getActiveSheet()->getRowDimension('8')->setRowHeight(27.75);
            $objPHPExcel->getActiveSheet()->getRowDimension('9')->setRowHeight(31);
            $objPHPExcel->getActiveSheet()->getRowDimension('10')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('15')->setRowHeight(65);
            $objPHPExcel->getActiveSheet()->getRowDimension('13')->setRowHeight(30);
            $objPHPExcel->getActiveSheet()->getRowDimension('14')->setRowHeight(37.5);
            $objPHPExcel->getActiveSheet()->getRowDimension('29')->setRowHeight(44.25);
            $objPHPExcel->getActiveSheet()->getRowDimension('30')->setRowHeight(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('31')->setRowHeight(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('32')->setRowHeight(1);
            $objPHPExcel->getActiveSheet()->getRowDimension('33')->setRowHeight(52.55);
            $objPHPExcel->getActiveSheet()->getRowDimension('16')->setRowHeight(36.75);
            $objPHPExcel->getActiveSheet()->getRowDimension('20')->setRowHeight(38.25);
            $objPHPExcel->getActiveSheet()->getRowDimension('22')->setRowHeight(55.5);

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

    public function alertMail($data){
        require "lib/class.phpmailer.php";
             $noidung = '<p>Vừa có 1 khách hàng xác nhận hợp đồng tuyến đường: <b> '.$data['tuyenduong'].'</b></p>
             <p> Container: <b>'.$data['feet'].'</b></p>
             <p> Loại container: <b>'.$data['loai'].'</b></p>
             <p> Số lượng: <b>'.$data['soluong'].'</b></p>
             <p> Số tấn/cont: <b>'.$data['sotan'].'</b></p>
             <p> Khách hàng: <b>'.$data['congty'].'</b></p>
             <p> Email: <b>'.$data['email'].'</b></p>
             <p> SĐT: <b>'.$data['sdt'].'</b></p>
             <p> Người liên hệ: <b>'.$data['contact'].'</b></p>
             <p style="color:red; font-size: 16px; font-weight:bold;"> *** Vui lòng liên hệ và làm hợp đồng cho khách hàng ***</p>';
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

            $mail->AltBody = "Hợp đồng vận chuyển - CMG";//Nội dung rút gọn hiển thị bên ngoài thư mục thư.
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