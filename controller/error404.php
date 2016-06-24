<?php

Class error404Controller Extends baseController {

    public function index() 
    {
        $this->view->data['blog_heading'] = 'This is the 404';
        $this->view->show('error404');
    }
}
?>