<?php

Class positionstaffworkController Extends baseController {

    public function index() {

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            return $this->view->redirect('user/login');
        }

        $this->view->data['lib'] = $this->lib;

        $this->view->data['title'] = 'Hệ số làm việc nhân viên';



        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;

            $order = isset($_POST['order']) ? $_POST['order'] : null;

            $page = isset($_POST['page']) ? $_POST['page'] : null;

            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;

            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;

        }

        else{

            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'position_staff_work_date';

            $order = $this->registry->router->order ? $this->registry->router->order : 'DESC';

            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;

            $keyword = "";

            $limit = 50;

        }

        $join = array('table'=>'staff','where'=>'staff_id = position_staff_work.staff');

        $data = array(
            'where' => '1=1',
            );


        $position_staff_work_model = $this->model->get('positionstaffworkModel');

        $sonews = $limit;

        $x = ($page-1) * $sonews;

        $pagination_stages = 2;

        

        $tongsodong = count($position_staff_work_model->getAllSalary($data,$join));

        $tongsotrang = ceil($tongsodong / $sonews);

        



        $this->view->data['page'] = $page;

        $this->view->data['order_by'] = $order_by;

        $this->view->data['order'] = $order;

        $this->view->data['keyword'] = $keyword;

        $this->view->data['limit'] = $limit;

        $this->view->data['pagination_stages'] = $pagination_stages;

        $this->view->data['tongsotrang'] = $tongsotrang;

        $this->view->data['sonews'] = $sonews;



        $data = array(

            'where' => '1=1',

            'order_by'=>$order_by,

            'order'=>$order,

            'limit'=>$x.','.$sonews,

            );


        if ($keyword != '') {

            $search = ' AND ( staff_name LIKE "%'.$keyword.'%" )';

            $data['where'] .= $search;

        }

        

        if ($this->registry->router->param_id==-1) {
            $id = $this->registry->router->page;
            $ngaytao = $this->registry->router->order_by;
            $data = array(
                'where'=>'staff = '.$id.' AND position_staff_work_date >= '.$ngaytao.' AND position_staff_work_date <= '.strtotime(date('t-m-Y',$ngaytao)),
            );
        }

        

        $this->view->data['positions'] = $position_staff_work_model->getAllSalary($data,$join);



        $this->view->data['lastID'] = isset($position_staff_work_model->getLastSalary()->position_staff_work_id)?$position_staff_work_model->getLastSalary()->position_staff_work_id:0;

        

        $this->view->show('positionstaffwork/index');

    }



    public function add(){

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }


        if (isset($_POST['yes'])) {

            $position_staff_work_model = $this->model->get('positionstaffworkModel');
            $data = array(

                        'position_staff_work_percent' => trim($_POST['position_staff_work_percent']),

                        'position_staff_work_date' => strtotime(trim($_POST['position_staff_work_date'])),

                        'staff' => trim($_POST['staff']),

                        );

            if ($_POST['yes'] != "") {

                    $position_staff_work_model->updateSalary($data,array('position_staff_work_id' => trim($_POST['yes'])));

                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|position_staff_work|".implode("-",$data)."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);

                    

            }

            else{

                //$data['driver_create_user'] = $_SESSION['userid_logined'];

                //$data['staff'] = $_POST['staff'];

                //var_dump($data);

                if ($position_staff_work_model->getSalaryByWhere(array('position_staff_work_date'=>$data['position_staff_work_date'],'staff'=>$data['staff']))) {

                    echo "Thông tin này đã tồn tại";

                    return false;

                }

                else{

                    
                    $position_staff_work_model->createSalary($data);

                    

                    echo "Thêm thành công";


                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$position_staff_work_model->getLastSalary()->position_staff_work_id."|position_staff_work|".implode("-",$data)."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);

                }

                

            }

                    

        }

    }



    

    



    public function delete(){

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $position_staff_work_model = $this->model->get('positionstaffworkModel');

            if (isset($_POST['xoa'])) {

                $data = explode(',', $_POST['xoa']);

                foreach ($data as $data) {
                    $position_staff_work_model->deleteSalary($data);
                    
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|position_staff_work|"."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);

                }

                return true;

            }

            else{

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|driver|"."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);



                return $position_staff_work_model->deleteSalary($_POST['data']);

            }

            

        }

    }



    


}

?>