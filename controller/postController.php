<?php
Class postController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý bài viết';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'post_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }
        

        $id = $this->registry->router->param_id;

        $post_model = $this->model->get('postModel');

        $join = array('table'=>'user','where'=>'post_user=user_id');

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;


        $data = array(
            'where' => '1=1',
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND post_id = '.$id;
        }
        
        $tongsodong = count($post_model->getAllPost($data,$join));
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

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND post_id = '.$id;
        }
        
        if ($keyword != '') {
            $search = ' AND ( post_title LIKE "%'.$keyword.'%" 
                OR post_desc LIKE "%'.$keyword.'%" )';
            $data['where'] .= $search;
        }
        $posts = $post_model->getAllPost($data,$join);
        $this->view->data['posts'] = $posts;

        $this->view->data['lastID'] = isset($post_model->getLastPost()->post_id)?$post_model->getLastPost()->post_id:0;
        
        $this->view->show('post/index');
    }

    public function newpost(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Thêm bài viết';


        $this->view->show('post/newpost');
    }
    public function editpost($id){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if (!$id) {
            return $this->view->redirect('post');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Cập nhật bài viết';

        $post_model = $this->model->get('postModel');
        $posts = $post_model->getPost($id);
        $this->view->data['posts'] = $posts;

        if (!$posts) {
            return $this->view->redirect('post');
        }


        $photo_model = $this->model->get('photoModel');
        $photos = $photo_model->getAllPhoto(array('where'=>'photo_id IN ('.$posts->post_picture.')'));
        $this->view->data['photos'] = $photos;

        $this->view->show('post/editpost');
    }
    public function upload(){
        $this->view->disableLayout();

        $photo_model = $this->model->get('photoModel');

        $output_dir = "public/images/upload/";
        if(isset($_FILES["myfile"]))
        {
            $ret = array();

            $error =$_FILES["myfile"]["error"];
            //You need to handle  both cases
            //If Any browser does not support serializing of multiple files using FormData() 
            if(!is_array($_FILES["myfile"]["name"])) //single file
            {
                $fileName = $_FILES["myfile"]["name"];
                $fullpath = $output_dir.$fileName;
                $file_info = pathinfo($fullpath);
                $uploaded_filename = $file_info['filename'];

                $count = 1;                 
                while (file_exists($fullpath)) {
                  $info = pathinfo($fullpath);
                  $fullpath = $info['dirname'] . '/' . $uploaded_filename
                  . '(' . $count++ . ')'
                  . '.' . $info['extension'];
                }
                move_uploaded_file($_FILES["myfile"]["tmp_name"],$fullpath);

                $data = array(
                    'photo_url'=>$fullpath,
                    'photo_create_user'=>$_SESSION['userid_logined'],
                    'photo_create_date'=>strtotime(date('d-m-Y')),
                );
                $photo_model->createPhoto($data);

                $ret[]= substr($fullpath, strripos($fullpath, "/") + 1);
            }
            else  //Multiple files, file[]
            {
              $fileCount = count($_FILES["myfile"]["name"]);
              for($i=0; $i < $fileCount; $i++)
              {
                $fileName = $_FILES["myfile"]["name"][$i];
                $fullpath = $output_dir.$fileName;
                $file_info = pathinfo($fullpath);
                $uploaded_filename = $file_info['filename'];

                $count = 1;                 
                while (file_exists($fullpath)) {
                  $info = pathinfo($fullpath);
                  $fullpath = $info['dirname'] . '/' . $uploaded_filename
                  . '(' . $count++ . ')'
                  . '.' . $info['extension'];
                }
                move_uploaded_file($_FILES["myfile"]["tmp_name"][$i],$fullpath);
                
                $data = array(
                    'photo_url'=>$fullpath,
                    'photo_create_user'=>$_SESSION['userid_logined'],
                    'photo_create_date'=>strtotime(date('d-m-Y')),
                );
                $photo_model->createPhoto($data);

                $ret[]= substr($fullpath, strripos($fullpath, "/") + 1);
              }
            
            }
            echo json_encode($ret);
         }
    }
    public function deletephoto(){
        $this->view->disableLayout();

        $photo_model = $this->model->get('photoModel');

        $output_dir = "public/images/upload/";
        if(isset($_POST["op"]) && $_POST["op"] == "delete" && isset($_POST['name']))
        {
            $fileName =$_POST['name'];
            $filePath = $output_dir. $fileName;
            if (file_exists($filePath)) 
            {
                unlink($filePath);

                $photo_model->queryPhoto('DELETE FROM photo WHERE photo_url ="'.$output_dir. $fileName.'"');
            }
            echo "Deleted File ".$fileName."<br>";
        }
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if (isset($_POST['yes'])) {
            $post = $this->model->get('postModel');
            $photo_model = $this->model->get('photoModel');

            $data = array(
                        'post_title' => trim($_POST['post_title']),
                        'post_desc' => stripslashes(trim($_POST['post_desc'])),
                        'post_content' => stripslashes(trim($_POST['post_content'])),
                        'post_link' => trim($_POST['post_link']),
                        'post_date' => strtotime(date('d-m-Y')),
                        'post_user' => $_SESSION['userid_logined'],
                        'post_tag' => trim($_POST['post_tag']),
                        );

            $data['post_picture'] = null;
            $post_picture = "";
            if(trim($_POST['post_picture']) != ""){
                $support = explode('|', trim($_POST['post_picture']));
                $output_dir = "public/images/upload/";
                if ($support) {
                    foreach ($support as $key) {
                        if ($name = $photo_model->getPhotoByWhere(array('photo_url'=>$output_dir.$key))) {
                            if ($post_picture == "")
                                $post_picture .= $name->photo_id;
                            else
                                $post_picture .= ','.$name->photo_id;
                        }
                        
                    }
                }

                $data['post_picture'] = $post_picture;
            }


            if ($_POST['yes'] != "") {

                if ($post->getAllPostByWhere($_POST['yes'].' AND post_link = "'.$data['post_link'].'"')) {
                    $mess = array(
                        'msg' => 'Tên bài viết đã tồn tại',
                        'id' => $_POST['yes'],
                    );

                    echo json_encode($mess);
                }
                else{
                    $post->updatePost($data,array('post_id' => $_POST['yes']));

                    /*Log*/
                    /**/

                    $mess = array(
                        'msg' => 'Cập nhật thành công',
                        'id' => $_POST['yes'],
                    );

                    echo json_encode($mess);

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|post|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                    
                
                
            }
            else{

                if ($post->getPostByWhere(array('post_link'=>$data['post_link']))) {
                    $mess = array(
                        'msg' => 'Tên bài viết đã tồn tại',
                        'id' => "",
                    );

                    echo json_encode($mess);
                    
                }
                else{
                    $post->createPost($data);

                    /*Log*/
                    /**/

                    $mess = array(
                        'msg' => 'Thêm thành công',
                        'id' => $post->getLastPost()->post_id,
                    );

                    echo json_encode($mess);

                    $doc = new DOMDocument();
                    $doc->load( 'sitemap.xml' );

                    $doc->formatOutput = true;
                    $r = $doc->getElementsByTagName("urlset")->item(0);

                    $b = $doc->createElement("url");

                    $loc = $doc->createElement("loc");
                    $loc->appendChild(
                        $doc->createTextNode(BASE_URL."/vn/tin-tuc/".$data['post_link'])
                    );
                    $b->appendChild( $loc );

                    $changefreq = $doc->createElement("changefreq");
                    $changefreq->appendChild(
                        $doc->createTextNode("weekly")
                    );
                    $b->appendChild( $changefreq );

                    $priority = $doc->createElement("priority");
                    $priority->appendChild(
                        $doc->createTextNode("0.80")
                    );
                    $b->appendChild( $priority );

                    $r->appendChild( $b );
                        
                    $doc->save("sitemap.xml");  


                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$post->getLastPost()->post_id."|post|".implode("-",$data)."\n"."\r\n";
                        
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
            $post = $this->model->get('postModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $post->deletePost($data);
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|post|"."\n"."\r\n";
                        
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
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|post|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                return $post->deletePost($_POST['data']);
            }
            
        }
    }

    public function getPost($id){
        return $this->getByID($this->table,$id);
    }

    private function getUrl(){

    }


}
?>