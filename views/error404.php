<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Page Not Found - <?php echo $_SERVER["SERVER_NAME"]=='caimeptrading.com'?'Cai Mep Trading':($_SERVER["SERVER_NAME"]=='www.caimeptrading.com'?'Cai Mep Trading':'Cai Mep Global Logistics') ?></title>
	<link href="<?php BASE_URL ?>/public/img/favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon">
	<link rel="stylesheet" type="text/css" href="<?php echo BASE_URL ?>/public/css/error.css">
</head>

<body>

	<div id="main" class="wrapper">
	            

	<div id="breadcrumbs">
	    <ul>
	        <li><a href="<?php echo $this->url('index') ?>">Trang chủ</a></li>
	       <li><a class="nolink">Page Not Found</a></li>
	    </ul>
	    <div id="bcaccount">
	        
	        <a href="<?php echo $this->url('user/login') ?>">Đăng nhập</a>
	    </div>
	</div>

	<h1 style="text-align:center; font-size:2.5em;">Oooh, bạn dường như đã đi chệch hướng.</h1>

	<p style="text-align:center; font-size:1.6em;">
	    <span style="font-style:italic;">Chúng tôi không thể tìm thấy trang bạn đang tìm kiếm. </span><br>
	    
	    <a href="<?php echo $this->url('index') ?>">Bấm vào đây để quay về trang chủ</a>
	    
	   <div id="img_error"></div>
	</p>
	</div>
</body>
</html>