<?php

Class Registry {

 /*
 * @the vars array
 * @access private
 */
 private $vars = array();


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

 /**
 *
 * @get variables
 *
 * @param mixed $index
 *
 * @return mixed
 *
 */
 public function __get($index)
 {
        return $this->vars[$index];
 }

}

/*
Chúng ta thấy có 2 phương thức tự động: __set() và __get() để tạo ra các biến sử dụng xuyên suốt trong chương trình như global variable. Cách sử dụng:
[php] $registry->newval = 'This is value of variable name newval';
echo $registry->newval; 
*/
?>