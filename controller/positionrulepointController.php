<?php

Class positionrulepointController Extends baseController {

    public function index() {

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            return $this->view->redirect('user/login');
        }

        $this->view->data['lib'] = $this->lib;

        $this->view->data['title'] = 'Khai báo hệ số kỷ luật';



        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;

            $order = isset($_POST['order']) ? $_POST['order'] : null;

            $page = isset($_POST['page']) ? $_POST['page'] : null;

            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;

            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;

        }

        else{

            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'position_rule_point_start_date';

            $order = $this->registry->router->order ? $this->registry->router->order : 'ASC';

            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;

            $keyword = "";

            $limit = 50;

        }



        $position_model = $this->model->get('positionruleModel');

        $positions = $position_model->getAllSalary(array('order_by'=>'position_rule_name','order'=>'ASC'));

        $this->view->data['position_datas'] = $positions;



        $join = array('table'=>'position_rule','where'=>'position_rule_id = position_rule_point.position_rule');



        $position_rule_point_model = $this->model->get('positionrulepointModel');

        $sonews = $limit;

        $x = ($page-1) * $sonews;

        $pagination_stages = 2;

        

        $tongsodong = count($position_rule_point_model->getAllSalary(null,$join));

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

            'order_by'=>$order_by,

            'order'=>$order,

            'limit'=>$x.','.$sonews,

            );

        

        if ($keyword != '') {

            $search = '( position_name LIKE "%'.$keyword.'%" )';

            $data['where'] = $search;

        }

        

        

        

        $this->view->data['positions'] = $position_rule_point_model->getAllSalary($data,$join);



        $this->view->data['lastID'] = isset($position_rule_point_model->getLastSalary()->position_rule_point_id)?$position_rule_point_model->getLastSalary()->position_rule_point_id:0;

        

        $this->view->show('positionrulepoint/index');

    }



    public function add(){

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }


        if (isset($_POST['yes'])) {

            $position_rule_point_model = $this->model->get('positionrulepointModel');
            $data = array(

                        'position_rule_point' => trim($_POST['position_rule_point']),

                        'position_rule_point_start_date' => strtotime(trim($_POST['position_rule_point_start_date'])),

                        'position_rule_point_end_date' => strtotime(trim($_POST['position_rule_point_end_date'])),

                        'position_rule' => trim($_POST['position_rule']),

                        );

            if ($_POST['yes'] != "") {

                    $position_d = $position_rule_point_model->getSalary($_POST['yes']);

                    $position1 = $position_rule_point_model->getSalaryByWhere(array('position_rule'=>$position_d->position_rule,'position_rule_point_end_date'=>(strtotime(date('d-m-Y',strtotime(date('d-m-Y',$position_d->position_rule_point_start_date).' -1 day'))))));
                    $position2 = $position_rule_point_model->getSalaryByWhere(array('position_rule'=>$position_d->position_rule,'position_rule_point_start_date'=>(strtotime(date('d-m-Y',strtotime(date('d-m-Y',$position_d->position_rule_point_end_date).' +1 day'))))));
                    if($position1)
                        $position_rule_point_model->updateSalary(array('position_rule'=>$position_d->position_rule,'position_rule_point_end_date'=>(strtotime(date('d-m-Y',strtotime($_POST['position_rule_point_start_date'].' -1 day'))))),array('position_rule_point_id' => $position1->position_rule_point_id));
                    if($position2)
                        $position_rule_point_model->updateSalary(array('position_rule'=>$position_d->position_rule,'position_rule_point_start_date'=>(strtotime(date('d-m-Y',strtotime($_POST['position_rule_point_end_date'].' +1 day'))))),array('position_rule_point_id' => $position2->position_rule_point_id));


                    $position_rule_point_model->updateSalary($data,array('position_rule_point_id' => trim($_POST['yes'])));

                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|position_rule_point|".implode("-",$data)."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);

                    

            }

            else{

                //$data['driver_create_user'] = $_SESSION['userid_logined'];

                //$data['staff'] = $_POST['staff'];

                //var_dump($data);

                if ($position_rule_point_model->getSalaryByWhere(array('position_rule'=>$data['position_rule'],'position_rule_point_start_date'=>$data['position_rule_point_start_date'],'position_rule_point_end_date'=>$data['position_rule_point_end_date']))) {

                    echo "Thông tin này đã tồn tại";

                    return false;

                }

                else{

                    $dm1 = $position_rule_point_model->querySalary('SELECT * FROM position_rule_point WHERE position_rule='.$data['position_rule'].' AND position_rule_point_start_date <= '.$data['position_rule_point_start_date'].' AND position_rule_point_end_date <= '.$data['position_rule_point_end_date'].' AND position_rule_point_end_date >= '.$data['position_rule_point_start_date'].' ORDER BY position_rule_point_end_date ASC LIMIT 1');
                    $dm2 = $position_rule_point_model->querySalary('SELECT * FROM position_rule_point WHERE position_rule='.$data['position_rule'].' AND position_rule_point_end_date >= '.$data['position_rule_point_end_date'].' AND position_rule_point_start_date >= '.$data['position_rule_point_start_date'].' AND position_rule_point_start_date <= '.$data['position_rule_point_end_date'].' ORDER BY position_rule_point_end_date ASC LIMIT 1');
                    $dm3 = $position_rule_point_model->querySalary('SELECT * FROM position_rule_point WHERE position_rule='.$data['position_rule'].' AND position_rule_point_start_date <= '.$data['position_rule_point_start_date'].' AND position_rule_point_end_date >= '.$data['position_rule_point_end_date'].' ORDER BY position_rule_point_end_date ASC LIMIT 1');

                    if ($dm3) {
                            foreach ($dm3 as $row) {
                                $d = array(
                                    'position_rule_point_end_date' => strtotime(date('d-m-Y',strtotime($_POST['position_rule_point_start_date'].' -1 day'))),
                                    );
                                $position_rule_point_model->updateSalary($d,array('position_rule_point_id'=>$row->position_rule_point_id));

                                $c = array(
                                    'position_rule_point' => $row->position_rule_point,
                                    'position_rule' => $row->position_rule,
                                    'position_rule_point_start_date' => strtotime(date('d-m-Y',strtotime($_POST['position_rule_point_end_date'].' +1 day'))),
                                    'position_rule_point_end_date' => $row->position_rule_point_end_date,
                                    );
                                $position_rule_point_model->createSalary($c);

                            }

                            

                            
                            $position_rule_point_model->createSalary($data);

                        }
                        else if ($dm1 || $dm2) {
                            if($dm1){
                                foreach ($dm1 as $row) {
                                    $d = array(
                                        'position_rule_point_end_date' => strtotime(date('d-m-Y',strtotime($_POST['position_rule_point_start_date'].' -1 day'))),
                                        );
                                    $position_rule_point_model->updateSalary($d,array('position_rule_point_id'=>$row->position_rule_point_id));

                                    
                                }
                            }
                            if($dm2){
                                foreach ($dm2 as $row) {
                                    $d = array(
                                        'position_rule_point_start_date' => strtotime(date('d-m-Y',strtotime($_POST['position_rule_point_end_date'].' +1 day'))),
                                        );
                                    $position_rule_point_model->updateSalary($d,array('position_rule_point_id'=>$row->position_rule_point_id));


                                }
                            }


                            
                            $position_rule_point_model->createSalary($data);

                        
                    }
                    else{
                        $position_rule_point_model->createSalary($data);

                    }

                    echo "Thêm thành công";


                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$position_rule_point_model->getLastSalary()->position_rule_point_id."|position_rule_point|".implode("-",$data)."\n"."\r\n";

                        

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

            $position_rule_point_model = $this->model->get('positionrulepointModel');

            if (isset($_POST['xoa'])) {

                $data = explode(',', $_POST['xoa']);

                foreach ($data as $data) {
                    $position_rule_point_model->deleteSalary($data);
                    
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|position_rule_point|"."\n"."\r\n";

                        

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



                return $position_rule_point_model->deleteSalary($_POST['data']);

            }

            

        }

    }



    


}

?>