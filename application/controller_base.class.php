<?php
Abstract Class baseController {

/*
 * @registry object
 */
protected $registry;
protected $model;
protected $view;
protected $lib;

function __construct($registry) {
    $this->registry = $registry;
    $this->model = baseModel::getInstance();
    $this->view  = baseView::getInstance();
    $this->lib  = Library::getInstance();
}


/**
 * @all controllers must contain an index method
 */
abstract function index();
}
?>