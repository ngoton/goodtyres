<?php
ob_start();
session_start();
 /*** error reporting on ***/
 error_reporting(E_ALL);

 /*** define the site path ***/
 $site_path = realpath(dirname(__FILE__));
 define ('__SITE_PATH', $site_path);

 /*** include the config.php file ***/
 include 'config.php';
  
 /*** include the init.php file ***/
 include 'includes/init.php';

if (isset($_COOKIE['remember']) && isset($_COOKIE['ui']) && isset($_COOKIE['up']) && $_COOKIE['remember'] == 1) {
 	$model = baseModel::getInstance();
 	$user = $model->get('user2Model');
    $row = $user->getUser(base64_decode(substr($_COOKIE['ui'], 2)));
    if($row->password == substr($_COOKIE['up'], 2)){
    	$_SESSION['user_logined'] = $row->username;
	    $_SESSION['userid_logined'] = $row->user_id;
	    $_SESSION['role_logined'] = $row->role;
    }
    else{
    	session_destroy();
        setcookie("remember", "",time() - 3600,"/");
        setcookie("uu", "",time() - 3600,"/");
        setcookie("ui", "",time() - 3600,"/");
        setcookie("ro", "",time() - 3600,"/");
        setcookie("up", "",time() - 3600,"/");
    }
    unset($user);
    unset($row);
}

/*** load the router ***/
 $registry->router = new router($registry); 

 /*** set the path to the controllers directory ***/
 $registry->router->setPath (__SITE_PATH . '/controller'); 
 $registry->router->loader();

 

?>