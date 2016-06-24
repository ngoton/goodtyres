<?php
Class chilipowderController Extends baseController {
    public function index() {
        /*** set a template variable ***/
            //$this->view->data['welcome'] = 'Welcome to CAI MEP TRADING !';
        /*** load the index template ***/
            $this->view->data['title'] = 'Chili Powder';
            $this->view->show('chili/index');
    }


}
?>