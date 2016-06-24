<?php
Class subscriptionController Extends baseController {
    public function index() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = isset($_POST['subscr_name']) ? $_POST['subscr_name'] : null;
            $email = isset($_POST['subscr_email']) ? $_POST['subscr_email'] : null;
            $phone = isset($_POST['subscr_phone']) ? $_POST['subscr_phone'] : null;
            $contact = isset($_POST['subscr_contact']) ? $_POST['subscr_contact'] : null;
            $redirect = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : null;



            $domain_name = substr(strrchr($email, "@"), 1);
            if ($domain_name == 'caimeptrading.com' || $domain_name == 'cmglogistics.com.vn' || $domain_name == 'vantaidaphuongthuc.com') {
                echo 'Vui lòng nhập đúng email của bạn';
                return false;
            }
            else{

                $dn = str_replace(" ", "+", $name);
                $url = 'http://hieudinh.dangkykinhdoanh.gov.vn/CheckExistName/tabid/63/scope/ALL/h/language/vi-VN/Default.aspx?q='.$dn;
                

                $result = $this->get_web_page($url);
                preg_match_all('/<span[^>]+id="dnn_ctr387_CheckExistEntName_lblSearchResult"[^>]*>(.*)<\/span>/', $result['content'], $title);
                $content = strip_tags($title[0][0]);
                
                
                if ($content == "1-1 của 1 kết quả" || $content == "1-2 của 2 kết quả" || $content == "1-3 của 3 kết quả" || $content == "1-4 của 4 kết quả") {
                    $customer = $this->model->get('ecustomerModel');
                    if (!$customer->getCustomerByWhere(array('e_customer_email'=>$email))) {
                        $data = array(
                        'e_customer_date' => strtotime(date('d-m-Y')),
                        'e_customer_co' => trim($name),
                        'e_customer_phone' => trim($phone),
                        'e_customer_email' => trim($email),
                        'e_customer_contact' => trim($contact),
                        );

                    $customer->createCustomer($data);
                    }
                    

                    echo 'OK';
                    return true;
                }
                else{
                    echo 'Vui lòng nhập đúng tên công ty của bạn';
                    return false;
                }
            }

            /*require("lib/class.verifyEmail.php");

            $vmail = new verifyEmail();

            if ($vmail->check($email)) {

                $dn = str_replace(" ", "+", $name);
                $url = 'http://hieudinh.dangkykinhdoanh.gov.vn/CheckExistName/tabid/63/scope/ALL/h/language/vi-VN/Default.aspx?q='.$dn;
                

                $result = $this->get_web_page($url);
                preg_match_all('/<span[^>]+id="dnn_ctr387_CheckExistEntName_lblSearchResult"[^>]*>(.*)<\/span>/', $result['content'], $title);
                $content = strip_tags($title[0][0]);
                
                
                if ($content == "1-1 của 1 kết quả" || $content == "1-2 của 2 kết quả" || $content == "1-3 của 3 kết quả" || $content == "1-4 của 4 kết quả") {
                    $customer = $this->model->get('ecustomerModel');
                    if (!$customer->getCustomerByWhere(array('e_customer_email'=>$email))) {
                        $data = array(
                        'e_customer_date' => strtotime(date('d-m-Y')),
                        'e_customer_co' => trim($name),
                        'e_customer_phone' => trim($phone),
                        'e_customer_email' => trim($email),
                        'e_customer_contact' => trim($contact),
                        );

                    $customer->createCustomer($data);
                    }
                    

                    echo 'OK';
                    return true;
                }
                else{
                    echo 'Vui lòng nhập đúng tên công ty của bạn';
                    return false;
                }

                

            } elseif ($vmail->isValid($email)) {
                //echo 'email &lt;' . $email . '&gt; valid, but not exist!';
                echo 'Vui lòng nhập vào email bạn đang sử dụng';
                return false;
            } else {
                //echo 'email &lt;' . $email . '&gt; not valid and not exist!';
                echo 'Vui lòng nhập vào email bạn đang sử dụng';
                return false;
            }*/



            $this->view->redirect($redirect);
        }
    }
    function get_web_page( $url )
    {
        $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        $options = array(

            CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
            CURLOPT_POST           =>false,        //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            CURLOPT_COOKIEFILE     =>"public/files/cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR      =>"public/files/cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        );

        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;
        return $header;
    }




}
?>