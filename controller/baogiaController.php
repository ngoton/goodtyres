<?php
Class indexController Extends baseController {
    public function index() {
        /*** set a template variable ***/
            $this->view->data['welcome'] = 'Welcome to My Blog!';
        /*** load the index template ***/
            $this->view->show('index');
    }

    public function view() {
        /*** set a template variable ***/
            $this->view->data['view'] = 'hehe';
        /*** load the index template ***/
            $this->view->show('index/view');
    }

}
?>