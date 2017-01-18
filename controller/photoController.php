<?php
Class photoController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý hình ảnh';

        $user_model = $this->model->get('userModel');
        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data[$user->user_id] = $user->username;
        }
        $this->view->data['user_data'] = $user_data;

        $photo_model = $this->model->get('photoModel');
        $batdau = '01-'.date('m-Y');
        $ketthuc = date('t-m-Y');

        $data = array(
            'where' => 'photo_create_date >= '.strtotime($batdau).' AND photo_create_date <= '.strtotime($ketthuc),
        );

        $photos = $photo_model->getAllPhoto($data);

        $photo_data = array();

        foreach ($photos as $photo) {
            $photo_data[date('d-m-Y',$photo->photo_create_date)][] = array('id'=>$photo->photo_id,'url'=>$photo->photo_url,'user'=>$photo->photo_create_user);
        }

        $this->view->data['photo_data'] = $photo_data;
        
        $this->view->data['photos'] = $photos;

        $this->view->show('photo/index');
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if (isset($_POST['yes'])) {
            $photo = $this->model->get('photoModel');
            $data = array(
                        
                        'photo_url' => trim($_POST['photo_url']),
                        );


            if ($_POST['yes'] != "") {
                //var_dump($data);
                if ($photo->getAllPhotoByWhere($_POST['yes'].' AND photo_url = "'.$data['photo_url'].'"')) {
                    echo "Tên đã được sử dụng";
                    return false;
                }
                
                
                else{
                    $photo->updatePhoto($data,array('photo_id' => $_POST['yes']));

                    /*Log*/
                    /**/
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|photo|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                
            }
            else{
                //var_dump($data);
                if ($photo->getPhotoByWhere(array('photo_url'=>$data['photo_url']))) {
                    echo "Tên đã được sử dụng";
                    return false;
                }
                
                else{

                    $photo->createPhoto($data);

                    /*Log*/
                    /**/

                    echo "Thêm thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$photo->getLastPhoto()->photo_id."|photo|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                
            }
                    
        }
    }
    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $photo = $this->model->get('photoModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $photo_data = $photo->getPhoto($data);
                    unlink($photo_data->photo_url);
                    $photo->deletePhoto($data);
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|photo|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }

                /*Log*/
                    /**/

                return true;
            }
            else{
                /*Log*/
                    /**/
                    $photo_data = $photo->getPhoto($_POST['data']);
                    unlink($photo_data->photo_url);

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|photo|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                return $photo->deletePhoto($_POST['data']);
            }
            
        }
    }

}
?>