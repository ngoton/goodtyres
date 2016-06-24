<?php
Class blogController Extends baseController {

    public function index() 
    {
        $this->view->data['blogs'] = $this->model->get('blogModel')->get_blogs();
        $this->view->data['blog_heading'] = 'This is the blog Index';
        $this->view->data['title'] = 'Trang chủ';
        //var_dump($this->lib->url_hientai());die();
        //$this->view->setLayout('layout');
        //$this->view->disableLayout();
        if (isset($_GET['link'])) {
          $this->crawl_page($_GET['link']);
        }
        
        //$this->view->show('blog/index');
        //var_dump($this->view->redirect('sds'));die();
        //$this->view->redirect('blog/view/1');
        //var_dump($this->registry->router);
    }

    public function view($args){
        $id_blog = $args;
        $blog_detail = $this->model->get('blogModel')->get_blog_detail($id_blog);
        $this->view->data['blog_heading'] = $blog_detail->name;
        $this->view->data['blog_content'] = $blog_detail->content;
        $this->view->helper('slidePaginator');
        $this->view->show('blog/view');
        
        //var_dump($this->view->data);die();
        //var_dump($this->registry->router);
    }
    function crawl_page($url){
        $original_file = file_get_contents($url);
      $stripped_file = strip_tags($original_file, "<a>");
      preg_match_all("/<a(?:[^>]*)href=\"([^\"]*)\"(?:[^>]*)>(?:[^<]*)<\/a>/is", $stripped_file, $matches);
     
      //DEBUGGING
     
      //$matches[0] now contains the complete A tags; ex: <a href="link">text</a>
      //$matches[1] now contains only the HREFs in the A tags; ex: link
     
      //header("Content-type: text/plain"); //Set the content type to plain text so the print below is easy to read!
      //print_r($matches); //View the array to see if it worked
      header("Content-type: application/json");
      //var_dump($matches[1]);
      echo json_encode($matches[1]);
    }
} 
?>