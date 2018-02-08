<?php

Class positionrevenueController Extends baseController {

    public function index() {

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            return $this->view->redirect('user/login');
        }

        $this->view->data['lib'] = $this->lib;

        $this->view->data['title'] = 'Khai báo phần trăm doanh thu';



        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;

            $order = isset($_POST['order']) ? $_POST['order'] : null;

            $page = isset($_POST['page']) ? $_POST['page'] : null;

            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;

            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;

        }

        else{

            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'position_revenue_start_date';

            $order = $this->registry->router->order ? $this->registry->router->order : 'ASC';

            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;

            $keyword = "";

            $limit = 50;

        }



        $position_model = $this->model->get('positionModel');

        $positions = $position_model->getAllSalary(array('order_by'=>'position_name','order'=>'ASC'));

        $this->view->data['position_datas'] = $positions;



        $join = array('table'=>'position','where'=>'position_id = position_revenue.position');



        $position_revenue_model = $this->model->get('positionrevenueModel');

        $sonews = $limit;

        $x = ($page-1) * $sonews;

        $pagination_stages = 2;

        

        $tongsodong = count($position_revenue_model->getAllSalary(null,$join));

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

        

        

        

        $this->view->data['positions'] = $position_revenue_model->getAllSalary($data,$join);



        $this->view->data['lastID'] = isset($position_revenue_model->getLastSalary()->position_revenue_id)?$position_revenue_model->getLastSalary()->position_revenue_id:0;

        

        $this->view->show('positionrevenue/index');

    }



    public function add(){

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }


        if (isset($_POST['yes'])) {

            $position_revenue_model = $this->model->get('positionrevenueModel');
            $data = array(

                        'position_revenue_percent' => trim($_POST['position_revenue_percent']),

                        'position_revenue_start_date' => strtotime(trim($_POST['position_revenue_start_date'])),

                        'position_revenue_end_date' => strtotime(trim($_POST['position_revenue_end_date'])),

                        'position' => trim($_POST['position']),

                        'position_revenue_type' => trim($_POST['position_revenue_type']),

                        );

            if ($_POST['yes'] != "") {

                    $position_d = $position_revenue_model->getSalary($_POST['yes']);

                    $position1 = $position_revenue_model->getSalaryByWhere(array('position'=>$position_d->position,'position_revenue_type'=>$position_d->position_revenue_type,'position_revenue_end_date'=>(strtotime(date('d-m-Y',strtotime(date('d-m-Y',$position_d->position_revenue_start_date).' -1 day'))))));
                    $position2 = $position_revenue_model->getSalaryByWhere(array('position'=>$position_d->position,'position_revenue_type'=>$position_d->position_revenue_type,'position_revenue_start_date'=>(strtotime(date('d-m-Y',strtotime(date('d-m-Y',$position_d->position_revenue_end_date).' +1 day'))))));
                    if($position1)
                        $position_revenue_model->updateSalary(array('position'=>$position_d->position,'position_revenue_type'=>$position_d->position_revenue_type,'position_revenue_end_date'=>(strtotime(date('d-m-Y',strtotime($_POST['position_revenue_start_date'].' -1 day'))))),array('position_revenue_id' => $position1->position_revenue_id));
                    if($position2)
                        $position_revenue_model->updateSalary(array('position'=>$position_d->position,'position_revenue_type'=>$position_d->position_revenue_type,'position_revenue_start_date'=>(strtotime(date('d-m-Y',strtotime($_POST['position_revenue_end_date'].' +1 day'))))),array('position_revenue_id' => $position2->position_revenue_id));


                    $position_revenue_model->updateSalary($data,array('position_revenue_id' => trim($_POST['yes'])));

                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|position_revenue|".implode("-",$data)."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);

                    

            }

            else{

                //$data['driver_create_user'] = $_SESSION['userid_logined'];

                //$data['staff'] = $_POST['staff'];

                //var_dump($data);

                if ($position_revenue_model->getSalaryByWhere(array('position'=>$data['position'],'position_revenue_type'=>$data['position_revenue_type'],'position_revenue_start_date'=>$data['position_revenue_start_date'],'position_revenue_end_date'=>$data['position_revenue_end_date']))) {

                    echo "Thông tin này đã tồn tại";

                    return false;

                }

                else{

                    $dm1 = $position_revenue_model->querySalary('SELECT * FROM position_revenue WHERE position='.$data['position'].' AND position_revenue_type='.$data['position_revenue_type'].' AND position_revenue_start_date <= '.$data['position_revenue_start_date'].' AND position_revenue_end_date <= '.$data['position_revenue_end_date'].' AND position_revenue_end_date >= '.$data['position_revenue_start_date'].' ORDER BY position_revenue_end_date ASC LIMIT 1');
                    $dm2 = $position_revenue_model->querySalary('SELECT * FROM position_revenue WHERE position='.$data['position'].' AND position_revenue_type='.$data['position_revenue_type'].' AND position_revenue_end_date >= '.$data['position_revenue_end_date'].' AND position_revenue_start_date >= '.$data['position_revenue_start_date'].' AND position_revenue_start_date <= '.$data['position_revenue_end_date'].' ORDER BY position_revenue_end_date ASC LIMIT 1');
                    $dm3 = $position_revenue_model->querySalary('SELECT * FROM position_revenue WHERE position='.$data['position'].' AND position_revenue_type='.$data['position_revenue_type'].' AND position_revenue_start_date <= '.$data['position_revenue_start_date'].' AND position_revenue_end_date >= '.$data['position_revenue_end_date'].' ORDER BY position_revenue_end_date ASC LIMIT 1');

                    if ($dm3) {
                            foreach ($dm3 as $row) {
                                $d = array(
                                    'position_revenue_end_date' => strtotime(date('d-m-Y',strtotime($_POST['position_revenue_start_date'].' -1 day'))),
                                    );
                                $position_revenue_model->updateSalary($d,array('position_revenue_id'=>$row->position_revenue_id));

                                $c = array(
                                    'position_revenue_percent' => $row->position_revenue_percent,
                                    'position' => $row->position,
                                    'position_revenue_type' => $row->position_revenue_type,
                                    'position_revenue_start_date' => strtotime(date('d-m-Y',strtotime($_POST['position_revenue_end_date'].' +1 day'))),
                                    'position_revenue_end_date' => $row->position_revenue_end_date,
                                    );
                                $position_revenue_model->createSalary($c);

                            }

                            

                            
                            $position_revenue_model->createSalary($data);

                        }
                        else if ($dm1 || $dm2) {
                            if($dm1){
                                foreach ($dm1 as $row) {
                                    $d = array(
                                        'position_revenue_end_date' => strtotime(date('d-m-Y',strtotime($_POST['position_revenue_start_date'].' -1 day'))),
                                        );
                                    $position_revenue_model->updateSalary($d,array('position_revenue_id'=>$row->position_revenue_id));

                                    
                                }
                            }
                            if($dm2){
                                foreach ($dm2 as $row) {
                                    $d = array(
                                        'position_revenue_start_date' => strtotime(date('d-m-Y',strtotime($_POST['position_revenue_end_date'].' +1 day'))),
                                        );
                                    $position_revenue_model->updateSalary($d,array('position_revenue_id'=>$row->position_revenue_id));


                                }
                            }


                            
                            $position_revenue_model->createSalary($data);

                        
                    }
                    else{
                        $position_revenue_model->createSalary($data);

                    }

                    echo "Thêm thành công";


                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$position_revenue_model->getLastSalary()->position_revenue_id."|position_revenue|".implode("-",$data)."\n"."\r\n";

                        

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

            $position_revenue_model = $this->model->get('positionrevenueModel');

            if (isset($_POST['xoa'])) {

                $data = explode(',', $_POST['xoa']);

                foreach ($data as $data) {
                    $position_revenue_model->deleteSalary($data);
                    
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|position_revenue|"."\n"."\r\n";

                        

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



                return $position_revenue_model->deleteSalary($_POST['data']);

            }

            

        }

    }



    


}

?>