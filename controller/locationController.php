<?php
Class locationController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý địa điểm';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'location_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
        }

        

        $location_model = $this->model->get('locationModel');
        $sonews = 15;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $join = array('table'=>'district','where'=>'location.district = district.district_id');

        
        $tongsodong = count($location_model->getAllLocation(null,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['sonews'] = $sonews;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            );
        
        if ($keyword != '') {
            $search = '( location_name LIKE "%'.$keyword.'%" 
                OR district_name LIKE "%'.$keyword.'%")';
            $data['where'] = $search;
        }
        $this->view->data['locations'] = $location_model->getAllLocation($data,$join);

        $this->view->show('location/index');
    }

    public function add(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['title'] = 'Thêm địa điểm';
        
        /*Lấy danh sách tỉnh*/
        $district = $this->model->get('districtModel');
        $this->view->data['district'] = $district->getAllDistrict();
        /*Thêm vào CSDL*/
        if (isset($_POST['submit'])) {
            if ($_POST['location_name'] != ''  && $_POST['district'] != '') {
                $location = $this->model->get('locationModel');

                $r = $location->getLocationByWhere(array('location_name'=>trim($_POST['location_name']),'district'=>trim($_POST['district'])));
                
                if (!$r) {
                    $data = array(
                        'location_name' => trim($_POST['location_name']),
                        'district' => trim($_POST['district']),
                        );
                    $location->createLocation($data);

                    

                    $this->view->data['error'] = "Thêm mới thành công";
                }
                else{
                     $this->view->data['error'] = "Tên địa điểm đã tồn tại";
                }
            }
        }
        return $this->view->show('location/add');
    }

    public function edit($id){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if (!$id) {
            $this->view->redirect('user');
        }
        $this->view->data['title'] = 'Cập nhật địa điểm';
        $location = $this->model->get('locationModel');
        $location_data = $location->getLocation($id);
        
        if (!$location_data) {
            $this->view->redirect('location');
        }
        else {
            
            $this->view->data['location'] = $location_data;
            /*Lấy danh sách tỉnh*/
            $district = $this->model->get('districtModel');
            $district_data = $district->getDistrict($location_data->district);
            $this->view->data['location_district'] = $district_data;
            $this->view->data['district'] = $district->getAllDistrictByWhere($district_data->district_id);
            /*Thêm vào CSDL*/
            if (isset($_POST['submit'])) {
                if ($_POST['district'] != '' && $_POST['location_name'] != '') {
                    
                    $check = $location->checkLocation($id,trim($_POST['location_name']),trim($_POST['district']));
                    if(!$check){
                        $data = array(
                            'location_name' => trim($_POST['location_name']),
                            'district' => trim($_POST['district']),
                            );
                    
                        $location->updateLocation($data,array('location_id'=>$id));
                        $this->view->data['error'] = "Cập nhật thành công";
                    }
                    else{
                        $this->view->data['error'] = "Tên địa điểm đã tồn tại";
                    }
                }
            }
        }
        
        return $this->view->show('location/edit');
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 5) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $location = $this->model->get('locationModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $location->deleteLocation($data);
                }
                return true;
            }
            else{
                return $location->deleteLocation($_POST['data']);
            }
            
        }
    }

    public function view() {
        
        $this->view->show('location/view');
    }

}
?>