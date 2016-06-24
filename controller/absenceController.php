<?php
Class absenceController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Nghỉ phép';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'absence_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
        }

        $join = array('table'=>'user','where'=>'user.user_id = absence.user');

        $absence_model = $this->model->get('absenceModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'absence_date >= '.strtotime($batdau).' AND absence_date <= '.strtotime($ketthuc),
        );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND user = '.$_SESSION['userid_logined'];
        }
        
        $tongsodong = count($absence_model->getAllAbsence($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'absence_date >= '.strtotime($batdau).' AND absence_date <= '.strtotime($ketthuc),
            );
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND user = '.$_SESSION['userid_logined'];
        }
      
        if ($keyword != '') {
            $search = '( comment LIKE "%'.$keyword.'%" 
                OR reason LIKE "%'.$keyword.'%" 
                OR username LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['absences'] = $absence_model->getAllAbsence($data,$join);
        $this->view->data['lastID'] = isset($absence_model->getLastAbsence()->absence_id)?$absence_model->getLastAbsence()->absence_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('absence/index');
    }

   public function send(){
        $absence_model = $this->model->get('absenceModel');
        $absence = $absence_model->getAbsenceByWhere(array('absence_id'=>$_POST['data']));

        $tg = 'từ ngày '.$this->lib->hien_thi_ngay_thang($absence->absence_from).' đến ngày '.$this->lib->hien_thi_ngay_thang($absence->absence_to);
        if ($absence->absence_from == $absence->absence_to) {
            $tg = 'ngày '.$this->lib->hien_thi_ngay_thang($absence->absence_from);
        }

        $staff_model = $this->model->get('staffModel');
        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

        $noidung = '<div>Kính gửi BGD và Phòng Nhân sự,</div>
                    <div>Tôi tên là: '.$staff->staff_name.'</div>
                    <div>Hôm nay, tôi viết đơn này xin nghỉ phép '.$tg.'</div>
                    <div>Lý do: '.$absence->reason.'</div>
                    <div>'.$absence->comment.'</div>';

    // Khai báo thư viên phpmailer
            require "lib/class.phpmailer.php";
             
            // Khai báo tạo PHPMailer
            $mail = new PHPMailer();
            //Khai báo gửi mail bằng SMTP
            $mail->IsSMTP();
            //Tắt mở kiểm tra lỗi trả về, chấp nhận các giá trị 0 1 2
            // 0 = off không thông báo bất kì gì, tốt nhất nên dùng khi đã hoàn thành.
            // 1 = Thông báo lỗi ở client
            // 2 = Thông báo lỗi cả client và lỗi ở server
            $mail->SMTPDebug  = 0;
             
            $mail->Debugoutput = "html"; // Lỗi trả về hiển thị với cấu trúc HTML
            $mail->Host       = "smtp.gmail.com"; //host smtp để gửi mail
            $mail->Port       = 587; // cổng để gửi mail
            $mail->SMTPSecure = "tls"; //Phương thức mã hóa thư - ssl hoặc tls
            $mail->SMTPAuth   = true; //Xác thực SMTP
            $mail->CharSet = 'UTF-8';
            $mail->Username   = "caimeptrading.com@gmail.com"; // Tên đăng nhập tài khoản Gmail
            $mail->Password   = "caimeptrading!@#"; //Mật khẩu của gmail
            $mail->SetFrom("caimeptrading.com@gmail.com", "CMG"); // Thông tin người gửi
            $mail->AddReplyTo($staff->staff_email,$staff->staff_name);// Ấn định email sẽ nhận khi người dùng reply lại.
            $mail->AddAddress("acct@caimeptrading.com", "Acct CMG");//Email của người nhận
            $mail->AddCC('karl@caimeptrading.com', 'Karl');
            $mail->AddCC($staff->staff_email,$staff->staff_name);
            $mail->Subject = "ĐƠN XIN NGHỈ PHÉP - ".$staff->staff_name; //Tiêu đề của thư
            $mail->IsHTML(true); // send as HTML   
            $mail->MsgHTML($noidung); //Nội dung của bức thư.
            // $mail->MsgHTML(file_get_contents("email-template.html"), dirname(__FILE__));
            // Gửi thư với tập tin html

            $mail->AltBody = "Đơn xin nghỉ phép";//Nội dung rút gọn hiển thị bên ngoài thư mục thư.
            //$mail->AddAttachment("images/attact-tui.gif");//Tập tin cần attach
            // For most clients expecting the Priority header:
            // 1 = High, 2 = Medium, 3 = Low
            $mail->Priority = 1;
            // MS Outlook custom header
            // May set to "Urgent" or "Highest" rather than "High"
            $mail->AddCustomHeader("X-MSMail-Priority: High");
            // Not sure if Priority will also set the Importance header:
            $mail->AddCustomHeader("Importance: High"); 
            $mail->Send();
            //Tiến hành gửi email và kiểm tra lỗi
   }

   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $absence = $this->model->get('absenceModel');
            $data = array(
                        'absence_date' => strtotime(date('d-m-Y')),
                        'comment' => trim($_POST['comment']),
                        'reason' => trim($_POST['reason']),
                        'absence_from' => strtotime(trim($_POST['absence_from'])),
                        'absence_to' => strtotime(trim($_POST['absence_to'])),
                        'user' => $_SESSION['userid_logined'],
                        );
            
            if ($_POST['yes'] != "") {
                


                    $absence->updateAbsence($data,array('absence_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|absence|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $absence->createAbsence($data);
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$absence->getLastAbsence()->absence_id."|absence|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
                    
        }
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $absence = $this->model->get('absenceModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $absence->deleteAbsence($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|absence|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $absence->deleteAbsence($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|absence|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

   

}
?>