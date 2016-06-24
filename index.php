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

if (isset($_COOKIE['remember']) && isset($_COOKIE['uu']) && $_COOKIE['remember'] == 1) {
	$_SESSION['user_logined'] = base64_decode(substr($_COOKIE['uu'], 2));
    $_SESSION['userid_logined'] = base64_decode(substr($_COOKIE['ui'], 2));
    $_SESSION['role_logined'] = base64_decode(substr($_COOKIE['ro'], 2));
}

/*** load the router ***/
 $registry->router = new router($registry); 

 /*** set the path to the controllers directory ***/
 $registry->router->setPath (__SITE_PATH . '/controller'); 
 $registry->router->loader();

 
?>