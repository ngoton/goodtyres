<?php
Class postController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 ) {
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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'post_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }

        

        $post_model = $this->model->get('postModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $join = array('table'=>'menu, user','where'=>'post.menu = menu.menu_id AND post.post_create_user = user.user_id');
        
        $tongsodong = count($post_model->getAllPost(null,$join));
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
            $search = '( post_title LIKE "%'.$keyword.'%" 
                OR post_description LIKE "%'.$keyword.'%" 
                OR menu_name LIKE "%'.$keyword.'%" 
                OR username LIKE "%'.$keyword.'%" 
                OR post_create_time LIKE "%'.$keyword.'%" 
                OR post_update_time LIKE "%'.$keyword.'%")';
            $data['where'] = $search;
        }
        $posts = $post_model->getAllPost($data,$join);
        $this->view->data['posts'] = $posts;

        $menu_model = $this->model->get('menuModel');
        $menu_data = array();
        foreach ($menu_model->getAllMenu() as $menu) {
            $menu_data[$menu->menu_id] = $menu->menu_name;
        }
        //echo json_encode($menu_data);
        $this->view->data['menu_data'] = $menu_data;

        $this->view->show('post/index');
    }

    public function add(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['title'] = 'Thêm bài viết';
        $post = $this->model->get('postModel');
        $menu = $this->model->get('menuModel');
        $this->view->data['menus'] = $menu->getAllMenu();
        /*Thêm vào CSDL*/
        if (isset($_POST['submit'])) {
            if ($_POST['post_title'] != '' && $_POST['post_content'] != ''  && $_POST['menu'] != '') {

                $r = $post->getPostByWhere(array('post_title'=>trim($_POST['post_title']),'menu'=>trim($_POST['menu'])));
                
                if (!$r) {
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    
                    $data = array(
                        'post_title' => trim($_POST['post_title']),
                        'post_description' => trim($_POST['post_description']),
                        'post_content' => trim($_POST['post_content']),
                        'post_create_user' => $_SESSION['userid_logined'],
                        'post_create_time' => date('d/m/Y H:i:s'),
                        'menu' => trim($_POST['menu']),
                        'link' => $this->lib->stripUnicode(trim($_POST['post_title'])),
                        );
                    if ($_FILES['post_image']['name'] != '') {
                            $this->lib->upload_image('post_image');
                            $data['post_image'] = $_FILES['post_image']['name'];
                        }

                    $post->createPost($data);

                    

                    $this->view->data['error'] = "Thêm mới thành công";
                }
                else{
                     $this->view->data['error'] = "Bài viết đã tồn tại";
                }
            }
        }
        return $this->view->show('post/add');
    }

    public function edit($id){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2) {
            return $this->view->redirect('user/login');
        }
        if (!$id) {
            $this->view->redirect('post');
        }
        $this->view->data['title'] = 'Cập nhật bài viết';
        $post = $this->model->get('postModel');
        $post_data = $post->getPost($id);
        
        if (!$post_data) {
            $this->view->redirect('post');
        }
        else {
            
            $this->view->data['post'] = $post_data;
            
            $menu = $this->model->get('menuModel');
            $this->view->data['menu_old'] = $menu->getMenu($post_data->menu);
            $this->view->data['menus'] = $menu->getAllMenuByWhere($post_data->menu);
            /*Thêm vào CSDL*/
            if (isset($_POST['submit'])) {
                if ($_POST['post_title'] != '' && $_POST['post_content'] != ''  && $_POST['menu'] != '') {
                    
                    $check = $post->checkPost($id,trim($_POST['post_title']),trim($_POST['menu']));

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    if(!$check){
                        $data = array(
                        'post_title' => trim($_POST['post_title']),
                        'post_description' => trim($_POST['post_description']),
                        'post_content' => trim($_POST['post_content']),
                        'post_update_user' => $_SESSION['userid_logined'],
                        'post_update_time' => date('d/m/Y H:i:s'),
                        'menu' => trim($_POST['menu']),
                        'link' => $this->lib->stripUnicode(trim($_POST['post_title'])),
                        );

                        if ($_FILES['post_image']['name'] != '') {
                            $this->lib->upload_image('post_image');
                            $data['post_image'] = $_FILES['post_image']['name'];
                        }
                    
                        $post->updatePost($data,array('post_id'=>$id));
                        $this->view->data['error'] = "Cập nhật thành công";
                    }
                    else{
                        $this->view->data['error'] = "Bài viết đã tồn tại";
                    }
                }
            }
        }
        
        return $this->view->show('post/edit');
    }

    public function delete(){
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $post = $this->model->get('postModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $link = $post->getPost($data);
                    unlink('public/images/upload/'.$link->post_image);
                    $post->deletePost($data);
                }
                return true;
            }
            else{
                $link = $post->getPost($_POST['data']);
                    unlink('public/images/upload/'.$link->post_image);
                return $post->deletePost($_POST['data']);
            }
            
        }
    }

    public function view() {
        
        $this->view->show('post/view');
    }
    public function post(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $post = $this->model->get('postModel');
            if ($_POST['post_title'] != '' && $_POST['post_content'] != ''  && $_POST['menu'] != '') {

                $r = $post->getPostByWhere(array('post_title'=>trim($_POST['post_title']),'menu'=>trim($_POST['menu'])));
                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    $img = "";
                    if ($_POST['post_image'] != '') {
                        $img = rand()."new_image.jpg";
                        $url = trim($_POST['post_image']); 
                        $file = file_get_contents($url); 
                        file_put_contents('public/images/upload/'.$img,$file);
                        
                    }
                    

                    $data = array(
                        'post_title' => trim($_POST['post_title']),
                        'post_description' => trim($_POST['post_description']),
                        'post_content' => trim($_POST['post_content']),
                        'post_image' => $img,
                        'menu' => trim($_POST['menu']),
                        'link' => $this->lib->stripUnicode(trim($_POST['post_title'])),
                        );

                if (!$r) {
                    $data['post_create_user'] = $_SESSION['userid_logined'];
                    $data['post_create_time'] = date('d/m/Y H:i:s');
                    $post->createPost($data);

                    

                    echo "Thêm mới thành công";
                }
                else{
                    $data['post_update_user'] = $_SESSION['userid_logined'];
                    $data['post_update_time'] = date('d/m/Y H:i:s');
                    $post->updatePost($data,array('post_id'=>$r->post_id));
                    echo "Cập nhật thành công";
                }
            }
        }
    }

    public function craw(){
        ini_set('max_execution_time', 300); //300 seconds = 5 minutes
        
        //get link;
        $str ="";

        for ($i=1; $i <=24 ; $i++) { 
            $url = "http://chutin.vn/danh-ba-doanh-nghiep/tim-kiem.aspx?city=982&p=".$i; 
            
            $content = $this->get_content_by_url($url); 
    
            $content = $this->get_content_by_tag($content,"<div id=\"div_arr_db\">",$url); 

            $str.=$content;

        }

        echo $str;
    
    }
    /// functions lay tin
    public static function get_content_by_url($url){
        $content = file_get_contents($url);
        do{
            $content = str_replace("  "," ",$content);
        }while(strpos($content,"  ",0)!==false);
        return $content;
    }
    
   
    public static function get_content_by_tag($content, $tag_and_more,$include_tag = true){
        $p = stripos($content,$tag_and_more,0);
        
        if($p===false) return "";
        $content=substr($content,$p);
        $p = stripos($content," ",0);
        if(abs($p)==0) return "";
        $open_tag = substr($content,0,$p);
        $close_tag = substr($open_tag,0,1)."/".substr($open_tag,1).">";
        
        $count_inner_tag = 0;
        $p_open_inner_tag = 1; 
        $p_close_inner_tag = 0;
        $count=1;
        do{
            $p_open_inner_tag = stripos($content,$open_tag,$p_open_inner_tag);
            $p_close_inner_tag = stripos($content,$close_tag,$p_close_inner_tag);
            $count++;
            if($p_close_inner_tag!==false) $p = $p_close_inner_tag;
            if($p_open_inner_tag!==false){
                if(abs($p_open_inner_tag)<abs($p_close_inner_tag)){
                    $count_inner_tag++;
                    $p_open_inner_tag++;
                }else{
                    $count_inner_tag--;
                    $p_close_inner_tag++;
                }
            }else{
                $count_inner_tag--;
                if($p_close_inner_tag>0) $p_close_inner_tag++;
            }
        }while($count_inner_tag>0);
        if($include_tag)
            return substr($content,0,$p+strlen($close_tag));
        else{
            $content = substr($content,0,$p);
            $p = stripos($content,">",0);
            return substr($content,$p+1);
        }
    }
   
    public static function fix_src_img_tag($content, $url){
        $p_start = 0;
        $start_tag = "<img";
        $loop = true;
        $double_ = true;
        if(substr($url,strlen($url)-1,1)=="/") $url = substr($url,0,strlen($url)-1);
        $src = "src=";
        $content=str_ireplace("src =",$src,$content);
        $content=str_ireplace("src= ",$src,$content);
        $len=0;
        do{
            $p_start = stripos($content,$start_tag,$p_start);
            $len=0;
            if($p_start!==false){
                $p_start=stripos($content,$src,$p_start+1);
                if($p_start>0){
                    $t = substr($content,strlen($src)+$p_start,1);
                    if($t=="\"" || $t=="'"){
                        $p_start += strlen($src)+1;
                    }else{
                        $p_start += strlen($src);
                    }
                    $content = substr($content,0,$p_start).$url.substr($content,$p_start);
                }
                $p_start+=$len+1;
            }else{
                $loop=false;
            }
        }while($loop);
        return $content;
    }
    public static function fix_src_link_tag($content, $url){
        $p_start = 0;
        $start_tag = "<a";
        $loop = true;
        $double_ = true;
        if(substr($url,strlen($url)-1,1)=="/") $url = substr($url,0,strlen($url)-1);
        $src = "src=";
        $content=str_ireplace("href =",$src,$content);
        $content=str_ireplace("href= ",$src,$content);
        $content=str_ireplace("href=",$src,$content);
        $len=0;
        do{
            $p_start = stripos($content,$start_tag,$p_start);
            $len=0;
            if($p_start!==false){
                $p_start=stripos($content,$src,$p_start+1);
                if($p_start>0){
                    $t = substr($content,strlen($src)+$p_start,1);
                    if($t=="\"" || $t=="'"){
                        $p_start += strlen($src)+1;
                    }else{
                        $p_start += strlen($src);
                    }
                    $content = substr($content,0,$p_start).$url.substr($content,$p_start);
                }
                $p_start+=$len+1;
            }else{
                $loop=false;
            }
        }while($loop);
        return $content;
    }
    
   
    public static function list_all_link($content, $url, $attribute = "class", $remove_image_link=true){
        $list = array();
        $bool = true;
        $i=0;
        $href="";
        $title="";
        $attr = "";
        $content = str_ireplace("href =","href=",$content);
        $content = str_ireplace("href= ","href=",$content); 
        $content = str_ireplace($attribute." =",$attribute."=",$content);
        $content = str_ireplace($attribute."= ",$attribute."=",$content);
        do{
            $p_start = 0;
            $p_end = 0;
            $p_start = strpos($content,"<a",$p_start);
            if($p_start!==false){
                $p_end = strpos($content,"</a>",$p_start);
                if($p_end>0){
                    $temp = substr($content,$p_start,$p_end-$p_start);
                    $content = substr($content,$p_end+strlen("</a>"));
                    $p_start = strpos($temp,"href=",0);
                    if($p_start>0){
                        $attr = $temp;
                        $temp=trim(substr($temp,$p_start+strlen("href=")));
                        $t= substr($temp,0,1);
                        if(($t=="\"") || ($t=="'")){
                            $p_end = strpos($temp,$t,1);
                            $href = substr($temp,1,$p_end-1);    
                        }else{
                            $p_start=strpos($temp," ",0);
                            $p_end=strpos($temp,">",0);
                            
                            if($p_start>0 && ($p_start<$p_end)){
                                $href=substr($temp,0,$p_start);
                            }else{
                                $href=substr($temp,0,$p_end);
                            }
                        }
                        $j=$i-1;
                        $p_end = strpos($temp,">",0);
                        $title=substr($temp,$p_end+1);
                        if($remove_image_link){
                            if(strpos($title,"<img",0)===false){
                                $j++;
                            }
                        }else{
                            $j++;
                        }
                        if($j==$i){
                            if(substr($href,0,1)=="/"){
                                $href=$url.$href; 
                            }  
                            $p_start = stripos($attr,$attribute."=",0);
                            if($p_start!==false){
                                $attr = substr($attr,$p_start+strlen($attribute."="));
                                $t = substr($attr,0,1);
                                if(($t=="\"") || ($t=="'")){
                                    $p_end = strpos($attr,$t,1);
                                    $attr = substr($attr,1,$p_end-1);
                                }else{
                                    $p_start = strpos($attr," ",0);
                                    $p_end = strpos($attr,">",0);
                                    if($p_star!==false){
                                        if($p_end===false || $p_end > $p_start)
                                            $attr = substr($attr,0,$p_start);
                                        else
                                            $attr = substr($attr,0,$p_end);
                                    }else if($p_end!==false){
                                        $attr = substr($attr,0,$p_end);
                                    }else{
                                        $attr = "";
                                    }
                                }
                            }else{
                                $attr = "";
                            }
                            $title=trim(str_replace("&nbsp;"," ",$title));
                            $list[$j]['href']=$href;
                            $list[$j]['title']=trim($title);
                            $list[$j][$attribute]=$attr;
                            $i++;
                        }
                    }
                }
            }else{
                $bool=false;
            }
        }while($bool);
        return $list;
    }

}
?>