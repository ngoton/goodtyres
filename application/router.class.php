<?php

class router {
 /*
 * @the registry
 */
 private $registry;

 /*
 * @the controller path
 */
 private $path;

 private $args = array();

 public $file;

 public $controller;

 public $action; 

 public $param_id;

 public $page;

 public $order_by;

 public $order;

 public $addition;

 function __construct($registry) {
        $this->registry = $registry;
 } 
 /**
 *
 * @set controller directory path
 *
 * @param string $path
 *
 * @return void
 *
 */
 function setPath($path) {

        /*** check if path i sa directory ***/
        if (is_dir($path) == false)
        {
                throw new Exception ('Invalid controller path: `' . $path . '`');
        }
        /*** set the path ***/
        $this->path = $path;
} 
/**
 *
 * @load the controller
 *
 * @access public
 *
 * @return void
 *
 */
 public function loader()
 {
    /*** check the route ***/
    $this->getController();

    /*** if the file is not there diaf ***/
    if (is_readable($this->file) == false)
    {
        $this->file = $this->path.'/error404.php';
                $this->controller = 'error404';
    }

    /*** include the controller ***/
    include $this->file;

    /*** a new controller class instance ***/
    $class = $this->controller . 'Controller';
    $controller = new $class($this->registry);

    /*** check if the action is callable ***/
    if (is_callable(array($controller, $this->action)) == false)
    {
        $action = 'index';
    }
    else
    {
        $action = $this->action;
    }
    /*** run the action ***/
    if(!empty($this->param_id))
        $controller->$action($this->param_id);
    else
        $controller->$action();
 } 
 /**
 *
 * @get the controller
 *
 * @access private
 *
 * @return void
 *
 */
private function getController() {

    /*** get the route from the url ***/
    $route = (empty($_GET['rt'])) ? '' : $_GET['rt'];

    if (empty($route))
    {
        $route = 'index';
    }
    else
    {
        /*** get the parts of the route ***/
        $parts = explode('/', $route);
        $parts[0] = str_replace("-", "", $parts[0]); // gioi-thieu => gioithieu
        $this->controller = $parts[0];
        if(isset( $parts[1]))
        {
            $this->action = $parts[1];
        }
        if(isset( $parts[2]) && is_numeric($parts[2]))
        {
            $this->param_id = $parts[2];
        }
        if(isset( $parts[3]) && is_numeric($parts[3]))
        {
            $this->page = $parts[3];
        }
        if(isset( $parts[4]))
        {
            $this->order_by = $parts[4];
        }
        if(isset( $parts[5]) && (is_numeric($parts[5]) || ($parts[5] == 'ASC' || $parts[5] == 'DESC')))
        {
            $this->order = $parts[5];
        }
        if(isset( $parts[6]))
        {
            $this->addition = $parts[6];
        }
        if(isset( $parts[7]))
        {
            $count_args = count($parts);
            $k = 1;
            $args = array();
            for($i = 2; $i < $count_args; $i++)
                $args[$k++] = $parts[$i]; 
            $this->args = $args;
        }
    }

    if (empty($this->controller))
    {
        $this->controller = 'index';
    }

    /*** Get action ***/
    if (empty($this->action))
    {
        $this->action = 'index';
    }

    /*** set the file path ***/
    $this->file = $this->path .'/'. $this->controller . 'Controller.php';
} 
}