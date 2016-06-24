<?php

Class baseView {


/*
 * @Variables array
 * @access public
 */
public $data = array();
private static $instance;
private $layout = "layout";

/**
 *
 * @constructor
 *
 * @access public
 *
 * @return void
 *
 */
function __construct() {

}

public static function getInstance() {
    if (!self::$instance)
    {    
        self::$instance = new baseView();
    }
    return self::$instance;
}
    
 /**
 *
 * @set undefined vars
 *
 * @param string $index
 *
 * @param mixed $value
 *
 * @return void
 *
 */
 public function __set($index, $value)
 {
        $this->vars[$index] = $value;
 }

function setLayout($name){
    $this->layout = $name;
}
function disableLayout(){
    $this->layout = "";
}

function helper($name){
    $path_helper = __SITE_PATH . '/views' . '/helper' . '/' . $name . '.phtml';

    if (file_exists($path_helper) == false)
    {
        throw new Exception('Template not found in '. $path_helper);
        return false;
    }
    foreach ($this->data as $key => $value)
    {
        $$key = $value;
    }

    return include($path_helper);
     
}
public function url($url){
    //header("location:".BASE_URL.'/'.$url);
    $link = BASE_URL.'/'.$url;
    return $link;
}

function show($view) {
    


    $path_view = __SITE_PATH . '/views' . '/' . $view . '.phtml';

    if (file_exists($path_view) == false)
    {
        //throw new Exception('Template not found in '. $path_view);
        $this->disableLayout();
        $path_view = __SITE_PATH . '/views/error404.php';
        
    }
    // Load variables
    foreach ($this->data as $key => $value)
    {
        $$key = $value;
    }

    if ($this->layout == "") {
        include($path_view);
    }

    else if ($this->layout != "") {
        $path = __SITE_PATH . '/views' . '/layout' . '/' . $this->layout . '.phtml';

        if (file_exists($path) == false)
        {
            throw new Exception('Template not found in '. $path);
            return false;
        }

        ob_start();
        require($path_view);
        $rendered = ob_get_contents();
        ob_end_clean(); 

        $this->data['content'] = $rendered;
        // Load variables
        foreach ($this->data as $key => $value)
        {
            $$key = $value;
        }

        include ($path);  
    }
                 
}
/*
    * tự động chuyển tới trang yêu cầu
    * param: url (k bắt đầu với / )
    * return: null
    */
    public function redirect($url){
        header("location:".BASE_URL.'/'.$url);
        die();
    }


}

?>