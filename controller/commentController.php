<?php
Class commentController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Comment';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'comment_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }


        $join = array('table'=>'user','where'=>'comment.comment_user = user.user_id');

        $comment_model = $this->model->get('commentModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $data = array(
            'where' => '1=1',
        );

        $tongsodong = count($comment_model->getAllComment($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['limit'] = $limit;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1=1',
            );

        
        if ($keyword != '') {
            $search = ' AND ( user_name LIKE "%'.$keyword.'%" 
                    OR comment LIKE "%'.$keyword.'%" )';
            $data['where'] .= $search;
        }
        
        
        
        $this->view->data['comments'] = $comment_model->getAllComment($data,$join);

        $this->view->data['lastID'] = isset($comment_model->getLastComment()->comment_id)?$comment_model->getLastComment()->comment_id:0;
        
        $this->view->show('comment/index');
    }

    public function getcomment(){
        $this->view->disableLayout();
        $table = $this->registry->router->order_by;
        $record = $this->registry->router->page;
        $this->view->data['table'] = $table;
        $this->view->data['record'] = $record;

        $comment_model = $this->model->get('commentModel');
        $join = array('table'=>'user','where'=>'comment.comment_user = user.user_id');
        $data = array(
            'where' => 'comment_table="'.$table.'" AND comment_record='.$record,
        );
        $comments = $comment_model->getAllComment($data,$join);

        $this->view->data['comments'] = $comments;

        $this->view->show('comment/getcomment');
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $comment = $this->model->get('commentModel');
            $data = array(
                        
                        'comment' => trim($_POST['comment']),
                        'comment_date' => strtotime(date('d-m-Y H:i:s')),
                        'comment_user' => $_SESSION['userid_logined'],
                        'comment_table' => trim($_POST['table']),
                        'comment_record' => trim($_POST['record']),
                                                
                        );
            if ($_POST['yes'] != "") {

                $comment->updateComment($data,array('comment_id' => $_POST['yes']));
                        echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|comment|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
            }
            else{
                $comment->createComment($data);
                    echo "Thêm thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$comment->getLastComment()->comment_id."|comment|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
            }
                    
        }
    }
    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $comment = $this->model->get('commentModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                   
                        $comment->deleteComment($data);
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|comment|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                    
                        $comment->deleteComment($_POST['data']);
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|comment|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    

}
?>