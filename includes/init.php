<?php


 /*** include the controller class ***/
 include __SITE_PATH . '/application/' . 'controller_base.class.php';
 
  /*** include the view class ***/
 include __SITE_PATH . '/application/' . 'view_base.class.php';

   /*** include the model class ***/
 include __SITE_PATH . '/application/' . 'model_base.class.php';
 
 /*** include the registry class ***/
 include __SITE_PATH . '/application/' . 'registry.class.php';

 /*** include the router class ***/
 include __SITE_PATH . '/application/' . 'router.class.php';

 /*** include the library class ***/
 include __SITE_PATH . '/lib/' . 'lib.class.php';


 /*** a new registry object ***/
 $registry = new registry;
 
 
?>



