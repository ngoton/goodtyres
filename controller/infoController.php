<?php
Class infoController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Thông tin doanh nghiệp';

        $info_model = $this->model->get('infoModel');
        $this->view->data['infos'] = $info_model->getAllInfo();
        
        $this->view->show('info/index');
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if (isset($_POST['yes'])) {
            $info_model = $this->model->get('infoModel');
            $data = array(
                        
                        'info_company' => trim($_POST['info_company']),
                        'info_mst' => trim($_POST['info_mst']),
                        'info_address' => trim($_POST['info_address']),
                        'info_phone' => trim($_POST['info_phone']),
                        'info_email' => trim($_POST['info_email']),
                        'info_director' => trim($_POST['info_director']),
                        'info_general_accountant' => trim($_POST['info_general_accountant']),
                        'info_accountant' => trim($_POST['info_accountant']),
                        );


            if ($_POST['yes'] != "") {

                $info_model->updateInfo($data,array('info_id' => $_POST['yes']));

                    /*Log*/
                    /**/
                    $mess = array(
                        'msg' => 'Cập nhật thành công',
                        'id' => $_POST['yes'],
                    );

                    echo json_encode($mess);

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|info|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                
                
            }
            else{

                $info_model->createInfo($data);

                    /*Log*/
                    /**/

                    $mess = array(
                        'msg' => 'Thêm thành công',
                        'id' => $info_model->getLastInfo()->info_id,
                    );

                    echo json_encode($mess);

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$info_model->getLastInfo()->info_id."|place|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                
                
            }
                    
        }
    }
    


}
?>