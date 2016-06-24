<?php
Class cungungthucphamController Extends baseController {
    public function index() {
        /*** set a template variable ***/
            //$this->view->data['welcome'] = 'Welcome to CAI MEP TRADING !';
        /*** load the index template ***/
            $this->view->data['title'] = 'Cung ứng thực phẩm';
            $this->view->show('cungungthucpham/index');
    }

    public function view() {
        /*** set a template variable ***/
            $this->view->data['view'] = 'hehe';
        /*** load the index template ***/
            $this->view->show('index/view');
    }

}
?>