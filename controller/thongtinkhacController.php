<?php
Class thongtinkhacController Extends baseController {
    public function index() {
        /*** set a template variable ***/
            //$this->view->data['welcome'] = 'Welcome to CAI MEP TRADING !';
        /*** load the index template ***/
            $this->view->data['title'] = 'Thông tin khác';
            $this->view->show('thongtinkhac/index');
    }

    public function view() {
        /*** set a template variable ***/
            $this->view->data['view'] = 'hehe';
        /*** load the index template ***/
            $this->view->show('index/view');
    }

}
?>