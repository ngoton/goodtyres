<?php
class Library{
	private static $instance;

	public static function getInstance() {
        if (!self::$instance)
        {    
            self::$instance = new Library();
        }
        return self::$instance;
    }

	public function hien_thi_ngay_thang($day){
		$result = null;
		if ($day > 0) {
			$i = date('d',$day);
			$j = date('m',$day);
			$k = date('Y',$day);
			$result = $i.'/'.$j.'/'.$k;
		}
		return $result;
	}

	/*
	* Xuất ra ngẫu nhiên mã màu
	*/

	public function rand_color() {
	    return substr('00000' . dechex(mt_rand(0, 0xffffff)), -6);
	}


	/*
	* cắt đầu 1 xâu với số từ nhất định
	* param: xâu cần cắt, số lượng từ muốn cắt
	* return: xâu được cắt
	*/
	public function truncateString($str,$len,$charset="UTF-8"){
		$str = html_entity_decode($str,ENT_QUOTES,$charset);
		if (mb_strlen($str,$charset) > $len) {
			$arr = explode(' ', $str);
			$str = mb_substr($str, 0, $len, $charset);
			$arrRes = explode(' ', $str);
			$last = $arr[count($arrRes)-1];
			unset($arr);
			if (strcasecmp($arrRes[count($arrRes)-1], $last)) {
				unset($arrRes[count($arrRes)-1]);
			}
			return implode(' ', $arrRes)."...";
		}
		return $str;
	}

	

	/*
	* lấy đường link trang hiện tại
	* param: không
	* return: url hiện tại
	*/
	public function url_hientai(){
		$pageURL = 'http';
		if (!empty($_SERVER['HTTPS'])) {
			if ($_SERVER['HTTPS'] == on) {
				$pageURL .= "s";
			}		
		}
		$pageURL .= '://';
		if ($_SERVER['SERVER_PORT'] != "80") {
			$pageURL .= $_SERVER['SERVER_NAME'].":".$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
		}
		else{
			$pageURL .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		}
		return $pageURL;
	}

	/*
	* loại bỏ ký tự đặc biệt
	* param: chuỗi
	* return: chuỗi mới
	*/
	function cleanup_text($str){
            
        if(ini_get('magic_quotes_gpc'))
        {
            $data= stripslashes($str);
        }
        return mysql_real_escape_string($data);
    } 

    /*
	
    */
    public function upload_image($name){
    	if ($_FILES[$name]['type'] == "image/jpeg" || $_FILES[$name]['type'] == "image/png" || $_FILES[$name]['type'] == "image/gif") {
    		if ($_FILES[$name]['size'] > 5000000) {
    			echo 'File không được lớn hơn 5Mb';
    		}
    		else{
    			$path = "public/images/upload/";
    			$tmp_name = $_FILES[$name]['tmp_name'];
    			$type = $_FILES[$name]['type'];
    			$size = $_FILES[$name]['size'];
    			$name = $_FILES[$name]['name'];

    			move_uploaded_file($tmp_name, $path.$name);
    		}
    	}
    }
    public function upload_file($name){
    	
    			$path = "public/files/";
    			$tmp_name = $_FILES[$name]['tmp_name'];
    			$type = $_FILES[$name]['type'];
    			$size = $_FILES[$name]['size'];
    			$name = $_FILES[$name]['name'];

    			move_uploaded_file($tmp_name, $path.$name);
    	
    }

    /*
	
    */
    public function formatMoney($number, $fractional=false) {  
	    if ($fractional) {  
	    	if ($number == round($number)) {
	    		$number = round($number);
	    	}
	    	else{
	    		$number = rtrim(sprintf('%.2f', $number),"0");  
	    	}
	        
	    } 
	    else{
	    	$number = round($number);
	    } 
	    while (true) {  
	        $replaced = preg_replace('/(-?\d+)(\d\d\d)/', '$1,$2', $number);  
	        if ($replaced != $number) {  
	            $number = $replaced;  
	        } else {  
	            break;  
	        }  
	    }  
	    return $number;  
	}

	/*
	4 months, 2 weeks, 3 days, 1 hour, 49 minutes, 15 seconds ago
    */
	public function time_elapsed_string($datetime, $full = 1) {
	    $now = new DateTime;
	    $ago = new DateTime($datetime);
	    $diff = $now->diff($ago);

	    $diff->w = floor($diff->d / 7);
	    $diff->d -= $diff->w * 7;

	    $string = array(
	        'y' => 'year',
	        'm' => 'month',
	        'w' => 'week',
	        'd' => 'day',
	        'h' => 'hour',
	        'i' => 'minute',
	        's' => 'second',
	    );
	    foreach ($string as $k => &$v) {
	        if ($diff->$k) {
	            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
	        } else {
	            unset($string[$k]);
	        }
	    }

	    $string = array_slice($string, 0, $full);
	    return $string ? implode(', ', $string) . ' ago' : 'just now';
	}

	/*
	* Bỏ dấu
	*/ 
	public function stripUnicode($str){
	  if(!$str) return false;
	   $unicode = array(
		  'a'=>'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ|Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ|A',
		  'd'=>'đ|Đ|D',
		  'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ|É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ|E',
		  'i'=>'í|ì|ỉ|ĩ|ị|Í|Ì|Ỉ|Ĩ|Ị|I',
		  'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ|Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ|O',
		  'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự|Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự|U',
		  'y'=>'ý|ỳ|ỷ|ỹ|ỵ|Ý|Ỳ|Ỷ|Ỹ|Ỵ|Y',
		  'b'=>'B',
		  'c'=>'C',
		  'f'=>'F',
		  'g'=>'G',
		  'h'=>'H',
		  'j'=>'J',
		  'k'=>'K',
		  'l'=>'L',
		  'm'=>'M',
		  'n'=>'N',
		  'p'=>'P',
		  'q'=>'Q',
		  'r'=>'R',
		  's'=>'s',
		  't'=>'T',
		  'v'=>'V',
		  'w'=>'W',
		  'x'=>'X',
		  'z'=>'Z',
 
	   );
	foreach($unicode as $nonUnicode=>$uni) $str = preg_replace("/($uni)/i",$nonUnicode,$str);
	$str = str_replace( ' - ', ' ', $str );
	$str = str_replace( ';', '', $str );
	$str = str_replace( ':', '', $str );
	$str = str_replace( '(', '', $str );
	$str = str_replace( ')', '', $str );
	$str = str_replace( ',', '', $str );
	$str = str_replace( '/', '', $str );
	$str = str_replace( '_', '', $str );
	return $str;
	}
	/*
	** Chuyển số sang chữ
	*/
	function convert_number_to_words($number) {
 
		$hyphen      = ' ';
		$conjunction = ' ';
		$separator   = ' ';
		$negative    = 'âm ';
		$decimal     = ' phẩy ';
		$one		 = 'mốt';
		$ten         = 'lẻ';
		$pen		 = 'lăm';
		$hund  		 = 'không trăm';
		$dictionary  = array(
		0                   => 'Không',
		1                   => 'Một',
		2                   => 'Hai',
		3                   => 'Ba',
		4                   => 'Bốn',
		5                   => 'Năm',
		6                   => 'Sáu',
		7                   => 'Bảy',
		8                   => 'Tám',
		9                   => 'Chín',
		10                  => 'Mười',
		11                  => 'Mười một',
		12                  => 'Mười hai',
		13                  => 'Mười ba',
		14                  => 'Mười bốn',
		15                  => 'Mười lăm',
		16                  => 'Mười sáu',
		17                  => 'Mười bảy',
		18                  => 'Mười tám',
		19                  => 'Mười chín',
		20                  => 'Hai mươi',
		30                  => 'Ba mươi',
		40                  => 'Bốn mươi',
		50                  => 'Năm mươi',
		60                  => 'Sáu mươi',
		70                  => 'Bảy mươi',
		80                  => 'Tám mươi',
		90                  => 'Chín mươi',
		100                 => 'trăm',
		1000                => 'ngàn',
		1000000             => 'triệu',
		1000000000          => 'tỷ',
		1000000000000       => 'nghìn tỷ',
		1000000000000000    => 'ngàn triệu triệu',
		1000000000000000000 => 'tỷ tỷ'
		);
		 
		if (!is_numeric($number)) {
			return false;
		}
		 
		// if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
		// 	// overflow
		// 	trigger_error(
		// 	'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
		// 	E_USER_WARNING
		// 	);
		// 	return false;
		// }
		 
		if ($number < 0) {
			return $negative . $this->convert_number_to_words(abs($number));
		}
		 
		$string = $fraction = null;
		 
		if (strpos($number, '.') !== false) {
			list($number, $fraction) = explode('.', $number);
		}
		 
		switch (true) {
			case $number < 21:
				$string = $dictionary[$number];
			break;
			case $number < 100:
				$tens   = ((int) ($number / 10)) * 10;
				$units  = $number % 10;
				$string = $dictionary[$tens];
				if ($units) {
					$string .= mb_strtolower(( $hyphen . ($units==1?$one:($units==5?$pen:$dictionary[$units])) ), 'UTF-8');
				}
			break;
			case $number < 1000:
				$hundreds  = $number / 100;
				$remainder = $number % 100;
				$string = $dictionary[$hundreds] . ' ' . $dictionary[100];
				if ($remainder) {
					$string .= mb_strtolower(( $conjunction . ($remainder<10?$ten.$hyphen:null) . $this->convert_number_to_words($remainder) ), 'UTF-8');
				}
			break;
			default:
				$baseUnit = pow(1000, floor(log($number, 1000)));
				$numBaseUnits = (int) ($number / $baseUnit);
				$remainder = $number - ($numBaseUnits*$baseUnit);
				$string = $this->convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
				$dec = explode('.', ($number / $baseUnit));
				if (isset($dec[1]) && substr($dec[1], 0, 1) == "0") {
					$string .= ' '.$hund;
					if (substr($dec[1], 0, 2) == "00") {
						$string .= ' '.$ten;
					}
					
				}
				if ($remainder) {
					$string .= mb_strtolower(( $remainder < 100 ? $conjunction : $separator ), 'UTF-8');
					$string .= mb_strtolower($this->convert_number_to_words($remainder), 'UTF-8');
				}
			break;
			
		}
		 
		if (null !== $fraction && is_numeric($fraction)) {
			$string .= $decimal;
			$words = array();
			foreach (str_split((string) $fraction) as $number) {
				$words[] = $dictionary[$number];
			}
			$string .= implode(' ', $words);
		}
		 
		return $string;
	}
}

?>