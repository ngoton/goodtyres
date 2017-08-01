<?php
Class workplanController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Kế hoạch làm việc';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'start_date ASC, work_plan_owner';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = date( 'd-m-Y');
            $ketthuc = date( 'd-m-Y');
            $trangthai = 0;
        }

        $attachment_model = $this->model->get('attachmentModel');
        $attachments = $attachment_model->getAllAttachment();
        $attachment_data = array();
        foreach ($attachments as $attachment) {
            $attachment_data['name'][$attachment->attachment_id] = $attachment->attachment_name;
        }
        $this->view->data['attachment_data'] = $attachment_data;
        
        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array('where'=>'status=1'));
        $this->view->data['staffs'] = $staffs;
        $staff_data = array();
        foreach ($staffs as $staff) {
            $staff_data['name'][$staff->staff_id] = $staff->staff_name;
        }
        $this->view->data['staff_data'] = $staff_data;

        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));
        $this->view->data['staff_info'] = $staff;

        $work_model = $this->model->get('workplanModel');

        $and = "";
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $and .= ' AND ( work_plan_owner = '.$staff->staff_id.' )';
        }

        if ($trangthai>0) {
            $and .= ' AND (work_plan_owner = '.$trangthai.')';
        }

        $working = count($work_model->getAllWork(array('where' => '(work_plan_complete != 1 AND start_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).')'.$and)));
        $complete = count($work_model->getAllWork(array('where' => 'work_plan_complete = 1  AND work_plan_complete_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND work_plan_complete_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).$and)));
        $deadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND work_plan_complete_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).'))'.$and)));
        $worktotal = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND start_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND work_plan_complete_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).') OR (start_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND end_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).') )'.$and)));
        //$todayworking = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND start_date <= '.strtotime($ketthuc).') OR (work_plan_complete!=1 AND start_date >= '.strtotime($batdau).' AND start_date <= '.strtotime($ketthuc).') )'.$and)));
        $todaycomplete = count($work_model->getAllWork(array('where' => '(work_plan_complete = 1  AND work_plan_complete_date > '.strtotime('-1 day',strtotime($batdau)).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime($batdau)).')'.$and)));
        $todaydeadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime($batdau).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date > '.strtotime('-1 day',strtotime($batdau)).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime($batdau)).'))'.$and)));

        $this->view->data['working'] = $working;
        $this->view->data['complete'] = $complete;
        $this->view->data['deadline'] = $deadline;
        $this->view->data['worktotal'] = $worktotal;
        //$this->view->data['todayworking'] = $todayworking;
        $this->view->data['todaycomplete'] = $todaycomplete;
        $this->view->data['todaydeadline'] = $todaydeadline;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '((work_plan_complete != 1 AND start_date <= '.strtotime($ketthuc).') OR (work_plan_complete!=1 AND start_date >= '.strtotime($batdau).' AND start_date <= '.strtotime($ketthuc).') )',
        );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }

        $tongsodong = count($work_model->getAllWork($data));
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
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '((work_plan_complete != 1 AND start_date <= '.strtotime($ketthuc).') OR (work_plan_complete!=1 AND start_date >= '.strtotime($batdau).' AND start_date <= '.strtotime($ketthuc).') )',
            );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }
       

        if ($keyword != '') {
            $search = '( work_plan_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $works = $work_model->getAllWork($data);

        $this->view->data['works'] = $works;
        $this->view->data['lastID'] = isset($work_model->getLastWork()->work_plan_id)?$work_model->getLastWork()->work_plan_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('workplan/index');
    }
    public function todayworkcomplete() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Kế hoạch làm việc';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'start_date ASC, work_plan_owner';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = date( 'd-m-Y');
            $ketthuc = date( 'd-m-Y');
            $trangthai = 0;
        }

        $attachment_model = $this->model->get('attachmentModel');
        $attachments = $attachment_model->getAllAttachment();
        $attachment_data = array();
        foreach ($attachments as $attachment) {
            $attachment_data['name'][$attachment->attachment_id] = $attachment->attachment_name;
        }
        $this->view->data['attachment_data'] = $attachment_data;
        
        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array('where'=>'status=1'));
        $this->view->data['staffs'] = $staffs;
        $staff_data = array();
        foreach ($staffs as $staff) {
            $staff_data['name'][$staff->staff_id] = $staff->staff_name;
        }
        $this->view->data['staff_data'] = $staff_data;

        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));
        $this->view->data['staff_info'] = $staff;

        $work_model = $this->model->get('workplanModel');

        $and = "";
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $and .= ' AND ( work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $and .= ' AND (work_plan_owner = '.$trangthai.')';
        }

        $working = count($work_model->getAllWork(array('where' => '(work_plan_complete != 1 AND start_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).')'.$and)));
        $complete = count($work_model->getAllWork(array('where' => 'work_plan_complete = 1  AND work_plan_complete_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND work_plan_complete_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).$and)));
        $deadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND work_plan_complete_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).'))'.$and)));
        $worktotal = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND start_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND work_plan_complete_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).') OR (start_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND end_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).') )'.$and)));
        $todayworking = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND start_date <= '.strtotime($ketthuc).') OR (work_plan_complete!=1 AND start_date >= '.strtotime($batdau).' AND start_date <= '.strtotime($ketthuc).') )'.$and)));
        //$todaycomplete = count($work_model->getAllWork(array('where' => '(work_plan_complete = 1  AND work_plan_complete_date > '.strtotime('-1 day',strtotime($batdau)).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime($batdau)).')'.$and)));
        $todaydeadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime($batdau).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date > '.strtotime('-1 day',strtotime($batdau)).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime($batdau)).'))'.$and)));

        $this->view->data['working'] = $working;
        $this->view->data['complete'] = $complete;
        $this->view->data['deadline'] = $deadline;
        $this->view->data['worktotal'] = $worktotal;
        $this->view->data['todayworking'] = $todayworking;
        //$this->view->data['todaycomplete'] = $todaycomplete;
        $this->view->data['todaydeadline'] = $todaydeadline;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '(work_plan_complete = 1  AND work_plan_complete_date > '.strtotime('-1 day',strtotime($batdau)).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime($batdau)).')',
        );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }

        $tongsodong = count($work_model->getAllWork($data));
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
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '(work_plan_complete = 1  AND work_plan_complete_date > '.strtotime('-1 day',strtotime($batdau)).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime($batdau)).')',
            );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }
       

        if ($keyword != '') {
            $search = '( work_plan_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $works = $work_model->getAllWork($data);

        $this->view->data['works'] = $works;
        $this->view->data['lastID'] = isset($work_model->getLastWork()->work_plan_id)?$work_model->getLastWork()->work_plan_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('workplan/todayworkcomplete');
    }
    public function todaydeadline() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Kế hoạch làm việc';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'start_date ASC, work_plan_owner';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = date( 'd-m-Y');
            $ketthuc = date( 'd-m-Y');
            $trangthai = 0;
        }

        $attachment_model = $this->model->get('attachmentModel');
        $attachments = $attachment_model->getAllAttachment();
        $attachment_data = array();
        foreach ($attachments as $attachment) {
            $attachment_data['name'][$attachment->attachment_id] = $attachment->attachment_name;
        }
        $this->view->data['attachment_data'] = $attachment_data;
        
        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array('where'=>'status=1'));
        $this->view->data['staffs'] = $staffs;
        $staff_data = array();
        foreach ($staffs as $staff) {
            $staff_data['name'][$staff->staff_id] = $staff->staff_name;
        }
        $this->view->data['staff_data'] = $staff_data;

        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));
        $this->view->data['staff_info'] = $staff;

        $work_model = $this->model->get('workplanModel');

        $and = "";
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $and .= ' AND ( work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $and .= ' AND (work_plan_owner = '.$trangthai.')';
        }

        $working = count($work_model->getAllWork(array('where' => '(work_plan_complete != 1 AND start_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).')'.$and)));
        $complete = count($work_model->getAllWork(array('where' => 'work_plan_complete = 1  AND work_plan_complete_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND work_plan_complete_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).$and)));
        $deadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND work_plan_complete_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).'))'.$and)));
        $worktotal = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND start_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND work_plan_complete_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).') OR (start_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND end_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).') )'.$and)));
        $todayworking = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND start_date <= '.strtotime($ketthuc).') OR (work_plan_complete!=1 AND start_date >= '.strtotime($batdau).' AND start_date <= '.strtotime($ketthuc).') )'.$and)));
        $todaycomplete = count($work_model->getAllWork(array('where' => '(work_plan_complete = 1  AND work_plan_complete_date > '.strtotime('-1 day',strtotime($batdau)).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime($batdau)).')'.$and)));
        //$todaydeadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime($batdau).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date > '.strtotime('-1 day',strtotime($batdau)).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime($batdau)).'))'.$and)));

        $this->view->data['working'] = $working;
        $this->view->data['complete'] = $complete;
        $this->view->data['deadline'] = $deadline;
        $this->view->data['worktotal'] = $worktotal;
        $this->view->data['todayworking'] = $todayworking;
        $this->view->data['todaycomplete'] = $todaycomplete;
        //$this->view->data['todaydeadline'] = $todaydeadline;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '((work_plan_complete != 1 AND end_date < '.strtotime($batdau).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date > '.strtotime('-1 day',strtotime($batdau)).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime($batdau)).'))',
        );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }

        $tongsodong = count($work_model->getAllWork($data));
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
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '((work_plan_complete != 1 AND end_date < '.strtotime($batdau).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date > '.strtotime('-1 day',strtotime($batdau)).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime($batdau)).'))',
            );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }
       

        if ($keyword != '') {
            $search = '( work_plan_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $works = $work_model->getAllWork($data);

        $this->view->data['works'] = $works;
        $this->view->data['lastID'] = isset($work_model->getLastWork()->work_plan_id)?$work_model->getLastWork()->work_plan_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('workplan/todaydeadline');
    }
    public function getworkname(){

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {


            $work_model = $this->model->get('workplanModel');

            if ($_POST['keyword'] == "*") {

                $list = $work_model->getAllWork();

            }

            else{

                $data = array(

                'where'=>'( work_plan_name LIKE "%'.$_POST['keyword'].'%" )',

                );

                $list = $work_model->getAllWork($data);

            }

            foreach ($list as $rs) {

                // put in bold the written text

                $work_plan_name = $rs->work_plan_name;

                if ($_POST['keyword'] != "*") {

                    $work_plan_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->work_plan_name);

                }

                // add new option

                echo '<li onclick="set_item_work(\''.$rs->work_plan_id.'\',\''.$rs->work_plan_name.'\')">'.$work_plan_name.'</li>';

            }

        }

    }
    public function total() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Kế hoạch làm việc';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'start_date ASC, work_plan_owner';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = date( 'd-m-Y', strtotime( 'sunday last week' ) );
            $ketthuc = date( 'd-m-Y', strtotime( 'saturday this week' ) );
            $trangthai = 0;
        }

        $attachment_model = $this->model->get('attachmentModel');
        $attachments = $attachment_model->getAllAttachment();
        $attachment_data = array();
        foreach ($attachments as $attachment) {
            $attachment_data['name'][$attachment->attachment_id] = $attachment->attachment_name;
        }
        $this->view->data['attachment_data'] = $attachment_data;
        
        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array('where'=>'status=1'));
        $this->view->data['staffs'] = $staffs;
        $staff_data = array();
        foreach ($staffs as $staff) {
            $staff_data['name'][$staff->staff_id] = $staff->staff_name;
        }
        $this->view->data['staff_data'] = $staff_data;

        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));
        $this->view->data['staff_info'] = $staff;

        $work_model = $this->model->get('workplanModel');

        $and = "";
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $and .= ' AND ( work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $and .= ' AND (work_plan_owner = '.$trangthai.')';
        }

        $working = count($work_model->getAllWork(array('where' => '(work_plan_complete != 1 AND start_date <= '.strtotime($ketthuc).')'.$and)));
        $complete = count($work_model->getAllWork(array('where' => 'work_plan_complete = 1  AND work_plan_complete_date >= '.strtotime($batdau).' AND work_plan_complete_date <= '.strtotime($ketthuc).$and)));
        $deadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime($batdau).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime($batdau).' AND work_plan_complete_date <= '.strtotime($ketthuc).'))'.$and)));

        $this->view->data['working'] = $working;
        $this->view->data['complete'] = $complete;
        $this->view->data['deadline'] = $deadline;

        $todayworking = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND start_date <= '.strtotime(date('d-m-Y')).') OR (work_plan_complete!=1 AND start_date >= '.strtotime(date('d-m-Y')).' AND start_date <= '.strtotime(date('d-m-Y')).') )'.$and)));
        $todaycomplete = count($work_model->getAllWork(array('where' => '(work_plan_complete = 1  AND work_plan_complete_date > '.strtotime('-1 day',strtotime(date('d-m-Y'))).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime(date('d-m-Y'))).')'.$and)));
        $todaydeadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime(date('d-m-Y')).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date > '.strtotime('-1 day',strtotime(date('d-m-Y'))).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime(date('d-m-Y'))).'))'.$and)));

        $this->view->data['todayworking'] = $todayworking;
        $this->view->data['todaycomplete'] = $todaycomplete;
        $this->view->data['todaydeadline'] = $todaydeadline;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '((work_plan_complete != 1 AND start_date <= '.strtotime($ketthuc).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime($batdau).' AND work_plan_complete_date <= '.strtotime($ketthuc).') OR (start_date >= '.strtotime($batdau).' AND end_date <= '.strtotime($ketthuc).') )',
        );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }

        $tongsodong = count($work_model->getAllWork($data));
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
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '((work_plan_complete != 1 AND start_date <= '.strtotime($ketthuc).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime($batdau).' AND work_plan_complete_date <= '.strtotime($ketthuc).') OR (start_date >= '.strtotime($batdau).' AND end_date <= '.strtotime($ketthuc).') )',
            );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }
       

        if ($keyword != '') {
            $search = '( work_plan_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $works = $work_model->getAllWork($data);

        $this->view->data['works'] = $works;
        $this->view->data['lastID'] = isset($work_model->getLastWork()->work_plan_id)?$work_model->getLastWork()->work_plan_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('workplan/total');
    }
    public function working() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Công việc đang thực hiện';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'start_date ASC, work_plan_owner';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = date( 'd-m-Y', strtotime( 'sunday last week' ) );
            $ketthuc = date( 'd-m-Y', strtotime( 'saturday this week' ) );
            $trangthai = 0;
        }
        
        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array('where'=>'status=1'));
        $this->view->data['staffs'] = $staffs;
        $staff_data = array();
        foreach ($staffs as $staff) {
            $staff_data['name'][$staff->staff_id] = $staff->staff_name;
        }
        $this->view->data['staff_data'] = $staff_data;

        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));
        $this->view->data['staff_info'] = $staff;

        $work_model = $this->model->get('workplanModel');

        $and = "";
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $and .= ' AND ( work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $and .= ' AND (work_plan_owner = '.$trangthai.')';
        }

        $working = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND start_date <= '.strtotime($ketthuc).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime($batdau).' AND work_plan_complete_date <= '.strtotime($ketthuc).') OR (start_date >= '.strtotime($batdau).' AND end_date <= '.strtotime($ketthuc).') )'.$and)));
        $complete = count($work_model->getAllWork(array('where' => 'work_plan_complete = 1  AND work_plan_complete_date >= '.strtotime($batdau).' AND work_plan_complete_date <= '.strtotime($ketthuc).$and)));
        $deadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime($batdau).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime($batdau).' AND work_plan_complete_date <= '.strtotime($ketthuc).'))'.$and)));

        $this->view->data['working'] = $working;
        $this->view->data['complete'] = $complete;
        $this->view->data['deadline'] = $deadline;

        $todayworking = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND start_date <= '.strtotime(date('d-m-Y')).') OR (work_plan_complete!=1 AND start_date >= '.strtotime(date('d-m-Y')).' AND start_date <= '.strtotime(date('d-m-Y')).') )'.$and)));
        $todaycomplete = count($work_model->getAllWork(array('where' => '(work_plan_complete = 1  AND work_plan_complete_date > '.strtotime('-1 day',strtotime(date('d-m-Y'))).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime(date('d-m-Y'))).')'.$and)));
        $todaydeadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime(date('d-m-Y')).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date > '.strtotime('-1 day',strtotime(date('d-m-Y'))).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime(date('d-m-Y'))).'))'.$and)));

        $this->view->data['todayworking'] = $todayworking;
        $this->view->data['todaycomplete'] = $todaycomplete;
        $this->view->data['todaydeadline'] = $todaydeadline;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '(work_plan_complete != 1 AND start_date <= '.strtotime($ketthuc).')',
        );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= 'AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }

        $tongsodong = count($work_model->getAllWork($data));
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
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '(work_plan_complete != 1 AND start_date <= '.strtotime($ketthuc).')',
            );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }
       

        if ($keyword != '') {
            $search = '( work_plan_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $works = $work_model->getAllWork($data);

        $this->view->data['works'] = $works;
        $this->view->data['lastID'] = isset($work_model->getLastWork()->work_plan_id)?$work_model->getLastWork()->work_plan_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('workplan/working');
    }
    public function workcomplete() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Công việc đang thực hiện';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'start_date ASC, work_plan_owner';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = date( 'd-m-Y', strtotime( 'sunday last week' ) );
            $ketthuc = date( 'd-m-Y', strtotime( 'saturday this week' ) );
            $trangthai = 0;
        }
        
        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array('where'=>'status=1'));
        $this->view->data['staffs'] = $staffs;
        $staff_data = array();
        foreach ($staffs as $staff) {
            $staff_data['name'][$staff->staff_id] = $staff->staff_name;
        }
        $this->view->data['staff_data'] = $staff_data;

        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));
        $this->view->data['staff_info'] = $staff;

        $work_model = $this->model->get('workplanModel');

        $and = "";
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $and .= ' AND ( work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $and .= ' AND (work_plan_owner = '.$trangthai.')';
        }

        $working = count($work_model->getAllWork(array('where' => '(work_plan_complete != 1 AND start_date <= '.strtotime($ketthuc).')'.$and)));
        $complete = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND start_date <= '.strtotime($ketthuc).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime($batdau).' AND work_plan_complete_date <= '.strtotime($ketthuc).') OR (start_date >= '.strtotime($batdau).' AND end_date <= '.strtotime($ketthuc).') )'.$and)));
        $deadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime($batdau).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime($batdau).' AND work_plan_complete_date <= '.strtotime($ketthuc).'))'.$and)));

        $this->view->data['working'] = $working;
        $this->view->data['complete'] = $complete;
        $this->view->data['deadline'] = $deadline;

        $todayworking = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND start_date <= '.strtotime(date('d-m-Y')).') OR (work_plan_complete!=1 AND start_date >= '.strtotime(date('d-m-Y')).' AND start_date <= '.strtotime(date('d-m-Y')).') )'.$and)));
        $todaycomplete = count($work_model->getAllWork(array('where' => '(work_plan_complete = 1  AND work_plan_complete_date > '.strtotime('-1 day',strtotime(date('d-m-Y'))).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime(date('d-m-Y'))).')'.$and)));
        $todaydeadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime(date('d-m-Y')).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date > '.strtotime('-1 day',strtotime(date('d-m-Y'))).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime(date('d-m-Y'))).'))'.$and)));

        $this->view->data['todayworking'] = $todayworking;
        $this->view->data['todaycomplete'] = $todaycomplete;
        $this->view->data['todaydeadline'] = $todaydeadline;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'work_plan_complete = 1  AND work_plan_complete_date >= '.strtotime($batdau).' AND work_plan_complete_date <= '.strtotime($ketthuc),
        );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }

        $tongsodong = count($work_model->getAllWork($data));
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
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'work_plan_complete = 1  AND work_plan_complete_date >= '.strtotime($batdau).' AND work_plan_complete_date <= '.strtotime($ketthuc),
            );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }
       

        if ($keyword != '') {
            $search = '( work_plan_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $works = $work_model->getAllWork($data);

        $this->view->data['works'] = $works;
        $this->view->data['lastID'] = isset($work_model->getLastWork()->work_plan_id)?$work_model->getLastWork()->work_plan_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('workplan/workcomplete');
    }
    public function deadline() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Công việc đang thực hiện';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'start_date ASC, work_plan_owner';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = date( 'd-m-Y', strtotime( 'sunday last week' ) );
            $ketthuc = date( 'd-m-Y', strtotime( 'saturday this week' ) );
            $trangthai = 0;
        }
        
        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array('where'=>'status=1'));
        $this->view->data['staffs'] = $staffs;
        $staff_data = array();
        foreach ($staffs as $staff) {
            $staff_data['name'][$staff->staff_id] = $staff->staff_name;
        }
        $this->view->data['staff_data'] = $staff_data;

        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));
        $this->view->data['staff_info'] = $staff;

        $work_model = $this->model->get('workplanModel');

        $and = "";
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $and .= ' AND ( work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $and .= ' AND (work_plan_owner = '.$trangthai.')';
        }

        $working = count($work_model->getAllWork(array('where' => '(work_plan_complete != 1 AND start_date <= '.strtotime($ketthuc).')'.$and)));
        $complete = count($work_model->getAllWork(array('where' => 'work_plan_complete = 1  AND work_plan_complete_date >= '.strtotime($batdau).' AND work_plan_complete_date <= '.strtotime($ketthuc).$and)));
        $deadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND start_date <= '.strtotime($ketthuc).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime($batdau).' AND work_plan_complete_date <= '.strtotime($ketthuc).') OR (start_date >= '.strtotime($batdau).' AND end_date <= '.strtotime($ketthuc).') )'.$and)));

        $this->view->data['working'] = $working;
        $this->view->data['complete'] = $complete;
        $this->view->data['deadline'] = $deadline;

        $todayworking = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND start_date <= '.strtotime(date('d-m-Y')).') OR (work_plan_complete!=1 AND start_date >= '.strtotime(date('d-m-Y')).' AND start_date <= '.strtotime(date('d-m-Y')).') )'.$and)));
        $todaycomplete = count($work_model->getAllWork(array('where' => '(work_plan_complete = 1  AND work_plan_complete_date > '.strtotime('-1 day',strtotime(date('d-m-Y'))).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime(date('d-m-Y'))).')'.$and)));
        $todaydeadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime(date('d-m-Y')).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date > '.strtotime('-1 day',strtotime(date('d-m-Y'))).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime(date('d-m-Y'))).'))'.$and)));

        $this->view->data['todayworking'] = $todayworking;
        $this->view->data['todaycomplete'] = $todaycomplete;
        $this->view->data['todaydeadline'] = $todaydeadline;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '((work_plan_complete != 1 AND end_date < '.strtotime($batdau).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime($batdau).' AND work_plan_complete_date <= '.strtotime($ketthuc).'))',
        );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }

        $tongsodong = count($work_model->getAllWork($data));
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
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '((work_plan_complete != 1 AND end_date < '.strtotime($batdau).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime($batdau).' AND work_plan_complete_date <= '.strtotime($ketthuc).'))',
            );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }
       

        if ($keyword != '') {
            $search = '( work_plan_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $works = $work_model->getAllWork($data);

        $this->view->data['works'] = $works;
        $this->view->data['lastID'] = isset($work_model->getLastWork()->work_plan_id)?$work_model->getLastWork()->work_plan_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('workplan/deadline');
    }
    public function weekcomplete() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Kế hoạch làm việc';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'start_date ASC, work_plan_owner';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = date( 'd-m-Y', strtotime( 'sunday last week' ) );
            $ketthuc = date( 'd-m-Y', strtotime( 'saturday this week' ) );
            $trangthai = 0;
        }
        
        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array('where'=>'status=1'));
        $this->view->data['staffs'] = $staffs;
        $staff_data = array();
        $staff_account = array();
        foreach ($staffs as $staff) {
            $staff_data['name'][$staff->staff_id] = $staff->staff_name;
            $staff_account['name'][$staff->account] = $staff->staff_name;
            $staff_account['id'][$staff->account] = $staff->staff_id;
        }
        $this->view->data['staff_data'] = $staff_data;
        $this->view->data['staff_account'] = $staff_account;

        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));
        $this->view->data['staff_info'] = $staff;

        $work_model = $this->model->get('workplanModel');
        $work_detail_model = $this->model->get('workplandetailModel');

        $and = "";
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $and .= ' AND ( work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $and .= ' AND (work_plan_owner = '.$trangthai.')';
        }

        $working = count($work_model->getAllWork(array('where' => '(work_plan_complete != 1 AND start_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).')'.$and)));
        $complete = count($work_model->getAllWork(array('where' => 'work_plan_complete = 1  AND work_plan_complete_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND work_plan_complete_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).$and)));
        $deadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND work_plan_complete_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).'))'.$and)));

        $this->view->data['working'] = $working;
        $this->view->data['complete'] = $complete;
        $this->view->data['deadline'] = $deadline;

        $todayworking = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND start_date <= '.strtotime(date('d-m-Y')).') OR (work_plan_complete!=1 AND start_date >= '.strtotime(date('d-m-Y')).' AND start_date <= '.strtotime(date('d-m-Y')).') )'.$and)));
        $todaycomplete = count($work_model->getAllWork(array('where' => '(work_plan_complete = 1  AND work_plan_complete_date > '.strtotime('-1 day',strtotime(date('d-m-Y'))).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime(date('d-m-Y'))).')'.$and)));
        $todaydeadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime(date('d-m-Y')).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date > '.strtotime('-1 day',strtotime(date('d-m-Y'))).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime(date('d-m-Y'))).'))'.$and)));

        $this->view->data['todayworking'] = $todayworking;
        $this->view->data['todaycomplete'] = $todaycomplete;
        $this->view->data['todaydeadline'] = $todaydeadline;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $join = array('table'=>'user','where'=>'create_user = user_id');
        
        $data = array(
            'where' => '(work_plan_complete = 1 AND work_plan_complete_date >= '.strtotime($batdau).')',
        );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }

        $tongsodong = count($work_model->getAllWork($data,$join));
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
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '(work_plan_complete = 1 AND work_plan_complete_date >= '.strtotime($batdau).')',
            );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }
       

        if ($keyword != '') {
            $search = '( work_plan_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $works = $work_model->getAllWork($data,$join);

        $details = array();
        $max = 1;

        foreach ($works as $work) {
            $details[$work->work_plan_id] = $work_detail_model->getAllWork(array('where'=>'work_plan = '.$work->work_plan_id));
            if ($work->work_plan_number > $max) {
                $max = $work->work_plan_number;
            }
        }

        $this->view->data['max'] = $max;
        $this->view->data['details'] = $details;
        $this->view->data['works'] = $works;
        $this->view->data['lastID'] = isset($work_model->getLastWork()->work_plan_id)?$work_model->getLastWork()->work_plan_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('workplan/weekcomplete');
    }
    public function monthcomplete() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Kế hoạch làm việc';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'start_date ASC, work_plan_owner';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = '01-'.date( 'm-Y' );
            $ketthuc = date( 't-m-Y');
            $trangthai = 0;
        }
        
        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array('where'=>'status=1'));
        $this->view->data['staffs'] = $staffs;
        $staff_data = array();
        $staff_account = array();
        foreach ($staffs as $staff) {
            $staff_data['name'][$staff->staff_id] = $staff->staff_name;
            $staff_account['name'][$staff->account] = $staff->staff_name;
            $staff_account['id'][$staff->account] = $staff->staff_id;
        }
        $this->view->data['staff_data'] = $staff_data;
        $this->view->data['staff_account'] = $staff_account;

        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));
        $this->view->data['staff_info'] = $staff;

        $work_model = $this->model->get('workplanModel');
        $work_detail_model = $this->model->get('workplandetailModel');

        $and = "";
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $and .= ' AND ( work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $and .= ' AND (work_plan_owner = '.$trangthai.')';
        }

        $working = count($work_model->getAllWork(array('where' => '(work_plan_complete != 1 AND start_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).')'.$and)));
        $complete = count($work_model->getAllWork(array('where' => 'work_plan_complete = 1  AND work_plan_complete_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND work_plan_complete_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).$and)));
        $deadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND work_plan_complete_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).'))'.$and)));

        $this->view->data['working'] = $working;
        $this->view->data['complete'] = $complete;
        $this->view->data['deadline'] = $deadline;

        $todayworking = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND start_date <= '.strtotime(date('d-m-Y')).') OR (work_plan_complete!=1 AND start_date >= '.strtotime(date('d-m-Y')).' AND start_date <= '.strtotime(date('d-m-Y')).') )'.$and)));
        $todaycomplete = count($work_model->getAllWork(array('where' => '(work_plan_complete = 1  AND work_plan_complete_date > '.strtotime('-1 day',strtotime(date('d-m-Y'))).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime(date('d-m-Y'))).')'.$and)));
        $todaydeadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime(date('d-m-Y')).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date > '.strtotime('-1 day',strtotime(date('d-m-Y'))).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime(date('d-m-Y'))).'))'.$and)));

        $this->view->data['todayworking'] = $todayworking;
        $this->view->data['todaycomplete'] = $todaycomplete;
        $this->view->data['todaydeadline'] = $todaydeadline;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $join = array('table'=>'user','where'=>'create_user = user_id');
        
        $data = array(
            'where' => '(work_plan_complete = 1 AND work_plan_complete_date >= '.strtotime($batdau).')',
        );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }

        $tongsodong = count($work_model->getAllWork($data,$join));
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
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '(work_plan_complete = 1 AND work_plan_complete_date >= '.strtotime($batdau).')',
            );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }
       

        if ($keyword != '') {
            $search = '( work_plan_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $works = $work_model->getAllWork($data,$join);

        $details = array();
        $max = 1;

        foreach ($works as $work) {
            $details[$work->work_plan_id] = $work_detail_model->getAllWork(array('where'=>'work_plan = '.$work->work_plan_id));
            if ($work->work_plan_number > $max) {
                $max = $work->work_plan_number;
            }
        }

        $this->view->data['max'] = $max;
        $this->view->data['details'] = $details;
        $this->view->data['works'] = $works;
        $this->view->data['lastID'] = isset($work_model->getLastWork()->work_plan_id)?$work_model->getLastWork()->work_plan_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('workplan/monthcomplete');
    }
    public function totalcomplete() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Kế hoạch làm việc';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'start_date ASC, work_plan_owner';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $batdau = '01-01-'.date( 'Y');
            $ketthuc = '31-12-'.date( 'Y');
            $trangthai = 0;
        }
        
        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array('where'=>'status=1'));
        $this->view->data['staffs'] = $staffs;
        $staff_data = array();
        $staff_account = array();
        foreach ($staffs as $staff) {
            $staff_data['name'][$staff->staff_id] = $staff->staff_name;
            $staff_account['name'][$staff->account] = $staff->staff_name;
            $staff_account['id'][$staff->account] = $staff->staff_id;
        }
        $this->view->data['staff_data'] = $staff_data;
        $this->view->data['staff_account'] = $staff_account;

        $staff = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));
        $this->view->data['staff_info'] = $staff;

        $work_model = $this->model->get('workplanModel');
        $work_detail_model = $this->model->get('workplandetailModel');

        $and = "";
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $and .= ' AND ( work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $and .= ' AND (work_plan_owner = '.$trangthai.')';
        }

        $working = count($work_model->getAllWork(array('where' => '(work_plan_complete != 1 AND start_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).')'.$and)));
        $complete = count($work_model->getAllWork(array('where' => 'work_plan_complete = 1  AND work_plan_complete_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND work_plan_complete_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).$and)));
        $deadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) )).' AND work_plan_complete_date <= '.strtotime(date( 'd-m-Y', strtotime( 'sunday this week' ) )).'))'.$and)));

        $this->view->data['working'] = $working;
        $this->view->data['complete'] = $complete;
        $this->view->data['deadline'] = $deadline;

        $todayworking = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND start_date <= '.strtotime(date('d-m-Y')).') OR (work_plan_complete!=1 AND start_date >= '.strtotime(date('d-m-Y')).' AND start_date <= '.strtotime(date('d-m-Y')).') )'.$and)));
        $todaycomplete = count($work_model->getAllWork(array('where' => '(work_plan_complete = 1  AND work_plan_complete_date > '.strtotime('-1 day',strtotime(date('d-m-Y'))).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime(date('d-m-Y'))).')'.$and)));
        $todaydeadline = count($work_model->getAllWork(array('where' => '((work_plan_complete != 1 AND end_date < '.strtotime(date('d-m-Y')).') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date > '.strtotime('-1 day',strtotime(date('d-m-Y'))).' AND work_plan_complete_date < '.strtotime('+1 day',strtotime(date('d-m-Y'))).'))'.$and)));

        $this->view->data['todayworking'] = $todayworking;
        $this->view->data['todaycomplete'] = $todaycomplete;
        $this->view->data['todaydeadline'] = $todaydeadline;

        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $join = array('table'=>'user','where'=>'create_user = user_id');
        
        $data = array(
            'where' => '(start_date >= '.strtotime($batdau).' AND start_date <= '.strtotime($ketthuc).')',
        );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }

        $tongsodong = count($work_model->getAllWork($data,$join));
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
        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '(start_date >= '.strtotime($batdau).' AND start_date <= '.strtotime($ketthuc).')',
            );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            $data['where'] .= ' AND ( create_user = '.$_SESSION['userid_logined'].' OR work_plan_owner = '.$staff->staff_id.' )';
        }
        if ($trangthai>0) {
            $data['where'] .= ' AND work_plan_owner = '.$trangthai;
        }
       

        if ($keyword != '') {
            $search = '( work_plan_name LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $works = $work_model->getAllWork($data,$join);

        $details = array();
        $max = 1;

        foreach ($works as $work) {
            $details[$work->work_plan_id] = $work_detail_model->getAllWork(array('where'=>'work_plan = '.$work->work_plan_id));
            if ($work->work_plan_number > $max) {
                $max = $work->work_plan_number;
            }
        }

        $this->view->data['max'] = $max;
        $this->view->data['details'] = $details;
        $this->view->data['works'] = $works;
        $this->view->data['lastID'] = isset($work_model->getLastWork()->work_plan_id)?$work_model->getLastWork()->work_plan_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('workplan/totalcomplete');
    }

   

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if (isset($_POST['yes'])) {
            $staff_model = $this->model->get('staffModel');
            $staffs = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));
            $work_plan_model = $this->model->get('workplanModel');
            $work_plan_detail_model = $this->model->get('workplandetailModel');
            $attachment_model = $this->model->get('attachmentModel');

            $work_plan_detail = json_decode($_POST['work_plan_detail'],true);

            $time = explode('-', trim($_POST['work_plan_time']));

            $data = array(
                        'work_plan_date' => strtotime(date('d-m-Y')),
                        'work_plan_name' => trim($_POST['work_plan_name']),
                        'work_plan_comment' => trim($_POST['work_plan_comment']),
                        'work_plan_complete' => trim($_POST['work_plan_complete']),
                        'work_plan_number' => trim($_POST['work_plan_number']),
                        'start_date' => strtotime(str_replace('/', '-', $time[0])),
                        'end_date' => strtotime(str_replace('/', '-', $time[1])),
                        'work_plan_complete_date' => strtotime(str_replace('/', '-', $_POST['work_plan_complete_date'])),
                        );
            
            $contributor = $_POST['work_plan_owner'];

            /*if(is_array($_POST['work_plan_owner'])){

                foreach ($_POST['work_plan_owner'] as $key) {

                    if ($contributor == "")

                        $contributor .= $key;

                    else

                        $contributor .= ','.$key;

                }
            }*/

            $data['work_plan_owner'] = $contributor;
            $data['work_plan_point'] = trim($_POST['work_plan_point'])+round(trim($_POST['work_plan_point2'])/60,2);

            $ret = "";
            $output_dir = "public/files/";
            if(isset($_FILES["myfile"]))
            {
                
                $error =$_FILES["myfile"]["error"];
                //You need to handle  both cases
                //If Any browser does not support serializing of multiple files using FormData() 
                if(!is_array($_FILES["myfile"]["name"])) //single file
                {
                    $fileName = $_FILES["myfile"]["name"];
                    $fullpath = $output_dir.$fileName;
                    $file_info = pathinfo($fullpath);
                    $uploaded_filename = $file_info['filename'];

                    $count = 1;                 
                    while (file_exists($fullpath)) {
                      $info = pathinfo($fullpath);
                      $fullpath = $info['dirname'] . '/' . $uploaded_filename
                      . '(' . $count++ . ')'
                      . '.' . $info['extension'];
                    }
                    move_uploaded_file($_FILES["myfile"]["tmp_name"],$fullpath);

                    $data_attachment = array(
                        'attachment_link'=>$fullpath,
                        'attachment_name'=>str_replace('public/files/', '', $fullpath),
                        'attachment_user'=>$_SESSION['userid_logined'],
                        'attachment_date'=>strtotime(date('d-m-Y')),
                    );
                    $attachment_model->createAttachment($data_attachment);

                    $ret = $attachment_model->getLastAttachment()->attachment_id;
                }
                else  //Multiple files, file[]
                {
                  $fileCount = count($_FILES["myfile"]["name"]);
                  for($i=0; $i < $fileCount; $i++)
                  {
                    $fileName = $_FILES["myfile"]["name"][$i];
                    $fullpath = $output_dir.$fileName;
                    $file_info = pathinfo($fullpath);
                    $uploaded_filename = $file_info['filename'];

                    $count = 1;                 
                    while (file_exists($fullpath)) {
                      $info = pathinfo($fullpath);
                      $fullpath = $info['dirname'] . '/' . $uploaded_filename
                      . '(' . $count++ . ')'
                      . '.' . $info['extension'];
                    }
                    move_uploaded_file($_FILES["myfile"]["tmp_name"][$i],$fullpath);
                    
                    $data_attachment = array(
                        'attachment_link'=>$fullpath,
                        'attachment_name'=>str_replace('public/files/', '', $fullpath),
                        'attachment_user'=>$_SESSION['userid_logined'],
                        'attachment_date'=>strtotime(date('d-m-Y')),
                    );
                    $attachment_model->createAttachment($data_attachment);

                    if($ret==""){
                        $ret = $attachment_model->getLastAttachment()->attachment_id;
                    }
                    else{
                        $ret .= ','.$attachment_model->getLastAttachment()->attachment_id;
                    }

                  }
                
                }
            }

                $data['work_plan_attachment'] = $ret;


            if ($_POST['yes'] != "") {
                                    
                
                    $work_plan_model->updateWork($data,array('work_plan_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    $id_work_plan = $_POST['yes'];

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|work_plan|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    $num = 0;
                    foreach ($work_plan_detail as $v) {
                        $data_detail = array(
                            'work_plan_detail' => $v['work_plan_detail_name'],
                            'work_plan' => $id_work_plan,
                        );
                        if ($data_detail['work_plan_detail'] != "") {
                            $num++;
                            if ($v['work_plan_detail_id'] == "") {
                                $work_plan_detail_model->createWork($data_detail);
                            }
                            else{
                                $work_plan_detail_model->updateWork($data_detail,array('work_plan_detail_id' => $v['work_plan_detail_id']));
                            }
                        }
                        
                    }
                    $work_plan_model->updateWork(array('work_plan_number'=>$num),array('work_plan_id' => $id_work_plan));
                
                
            }
            else{

                $data['create_user'] = $_SESSION['userid_logined'];
                
                    $work_plan_model->createWork($data);

                    $id_work_plan = $work_plan_model->getLastWork()->work_plan_id;

                    echo "Thêm thành công";

                 
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$work_plan_model->getLastWork()->work_plan_id."|work_plan|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                    $num = 0;
                    foreach ($work_plan_detail as $v) {
                        $data_detail = array(
                            'work_plan_detail' => $v['work_plan_detail_name'],
                            'work_plan' => $id_work_plan,
                        );
                        if ($data_detail['work_plan_detail'] != "") {
                            $num++;
                            if ($v['work_plan_detail_id'] == "") {
                                $work_plan_detail_model->createWork($data_detail);
                            }
                            else{
                                $work_plan_detail_model->updateWork($data_detail,array('work_plan_detail_id' => $v['work_plan_detail_id']));
                            }
                        }
                        
                    }
                    $work_plan_model->updateWork(array('work_plan_number'=>$num),array('work_plan_id' => $id_work_plan));
                
                
            }

            
                    
        }
    }
    

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $work_plan_model = $this->model->get('workplanModel');
            $work_plan_detail_model = $this->model->get('workplandetailModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $work_plan_model->deleteWork($data);
                       //$assets_model->queryAssets('DELETE FROM assets WHERE sec = '.$data);
                       $work_plan_detail_model->queryWork('DELETE FROM work_plan_detail WHERE work_plan = '.$data);

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|work_plan|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $work_plan_model->deleteWork($_POST['data']);
                       //$assets_model->queryAssets('DELETE FROM assets WHERE sec = '.$data);
                       $work_plan_detail_model->queryWork('DELETE FROM work_plan_detail WHERE work_plan = '.$_POST['data']);

                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|work_plan|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }
    
    public function deletedetail(){



        if (isset($_POST['work_plan_detail'])) {

            $work_plan_detail_model = $this->model->get('workplandetailModel');

            $work_plan_detail_model->queryWork('DELETE FROM work_plan_detail WHERE work_plan_detail_id = '.$_POST['work_plan_detail']);



            echo 'Đã xóa thành công';



        }



    }
    public function deleteattach(){


        if (isset($_POST['data'])) {

            $work_plan_model = $this->model->get('workplanModel');
            $attachment_model = $this->model->get('attachmentModel');

            $attach = $attachment_model->getAttachmentByWhere(array('attachment_name'=>trim($_POST['val'])));
            $work_plan = $work_plan_model->getWorkByWhere(array('work_plan_id'=>trim($_POST['data'])));

            $tx = "";
            $attachment = explode(',', $work_plan->work_plan_attachment);
            foreach ($attachment as $key) {
                if ($key != $attach->attachment_id) {
                    if ($tx == "") {
                        $tx = $key;
                    }
                    else{
                        $tx .= ','.$key;
                    }
                }
            }
            $work_plan_model->updateWork(array('work_plan_attachment'=>$tx),array('work_plan_id'=>$_POST['data']));

            unlink($attach->attachment_link);
            $attachment_model->deleteAttachment($attach->attachment_id);

            echo 'Đã xóa thành công';

        }

    }

    public function complete(){



        if (isset($_POST['work_plan'])) {

            $work_plan_model = $this->model->get('workplanModel');

            $work_plan_model->updateWork(array('work_plan_complete'=>$_POST['work_plan_complete'],'work_plan_complete_date'=>strtotime(date('d-m-Y'))),array('work_plan_id'=>$_POST['work_plan']));


            echo 'Thành công';



        }



    }

    public function getdetail(){



        if(isset($_POST['work_plan'])){




            $work_plan_detail_model = $this->model->get('workplandetailModel');



            $data = array(



                'where' => 'work_plan = '.$_POST['work_plan'],



            );



            $works = $work_plan_detail_model->getAllWork($data);







            $str = "";



            if (!$works) {




                $str .= '<tr class="'.$_POST['work_plan'].'">';



                $str .= '<td><input type="checkbox"  name="chk" data=""></td>';



                $str .= '<td><table style="width: 100%">';



                $str .= '<tr class="'.$_POST['work_plan'] .'">';



                $str .= '<td>Nội dung</td>';



                $str .= '<td><input type="text" name="work_plan_detail[]" class="work_plan_detail" required="required"></td>';



                $str .= '</tr></table></td></tr>';



            }



            else{



                foreach ($works as $v) {



                    $str .= '<tr class="'.$_POST['work_plan'].'">';



                    $str .= '<td><input type="checkbox" name="chk" data="'.$v->work_plan_detail_id.'"  ></td>';



                    $str .= '<td><table style="width: 100%">';



                    $str .= '<tr class="'.$_POST['work_plan'] .'">';



                    $str .= '<td>Nội dung</td>';



                    $str .= '<td><input type="text" name="work_plan_detail[]" class="work_plan_detail" value="'.$v->work_plan_detail.'" required="required"></td>';



                    $str .= '</tr></table></td></tr>';



                }



            }







            echo $str;



        }



    }

    function exportstaff($mail,$objPHPExcel,$staff_model,$work_plan_model,$detail_model,$sti,$bd,$kt){

        $this->view->disableLayout();

        $staff = $sti;
        $batdau = $bd;
        $ketthuc = $kt;

        $staff_model = $staff_model;
        $work_model = $work_plan_model;
        $work_detail_model = $detail_model;
        
        $staffs = $staff_model->getAllStaff(array('where'=>'status=1'));
        $staff_data = array();
        foreach ($staffs as $st) {
            $staff_data['name'][$st->staff_id] = $st->staff_name;
        }


        $data = array(
            'order_by'=>'start_date',
            'order'=>'ASC',
            'where' => '((work_plan_complete != 1 AND end_date < '.$batdau.') OR (work_plan_complete != 1 AND start_date > '.strtotime('-1 day', $batdau).' AND start_date < '.strtotime('+1 day', $batdau).') )',
            );

        $add = "";
        if($staff > 0){

            $data['where'] .= ' AND work_plan_owner = '.$staff;

            $add = ' AND work_plan_owner = '.$staff;
        }

        

        $works = $work_model->getAllWork($data);

        

        $objPHPExcel = $objPHPExcel;



            



            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)

            $objPHPExcel->setActiveSheetIndex($index_worksheet)

                ->setCellValue('A2', 'KẾ HOẠCH LÀM VIỆC')

                ->setCellValue('A4', 'STT')

               ->setCellValue('B4', 'Tên công việc')

               ->setCellValue('C4', 'SL')

               ->setCellValue('D4', 'Thời gian')

               ->setCellValue('F4', 'Chi tiết')

               ->setCellValue('D5', 'Từ ngày')

               ->setCellValue('E5', 'Đến ngày');

            
            $numbers = $work_model->queryWork('SELECT max(work_plan_number) AS num FROM work_plan WHERE (work_plan_complete != 1 OR start_date >= '.$batdau.')'.$add);
            foreach ($numbers as $key) {
                $max = $key->num;
            }

            $dvt = "E";
            for ($i=1; $i<=$max; $i++) {
                $dvt++;
                $objPHPExcel->setActiveSheetIndex($index_worksheet)->setCellValue($dvt.'5', $i);
            }


            $objPHPExcel->getActiveSheet()->mergeCells('D4:E4');
            $objPHPExcel->getActiveSheet()->mergeCells('F4:'.($dvt++).'4');

            $g = $dvt;

           $objPHPExcel->setActiveSheetIndex($index_worksheet)

           ->setCellValue($dvt.'4', 'PIC')
           ->setCellValue(($dvt++).'5', 'Giao');

           $objPHPExcel->getActiveSheet()->mergeCells($g.'4:'.$dvt.'4');

           $objPHPExcel->setActiveSheetIndex($index_worksheet)

               ->setCellValue(($dvt++).'5', 'Thực hiện')
               ->setCellValue($dvt.'4', 'Ghi chú');

            $g = $dvt;

            $objPHPExcel->setActiveSheetIndex($index_worksheet)

               ->setCellValue(($dvt++).'5', 'Note')
               ->setCellValue(($dvt++).'5', 'Hoàn thành')
               ->setCellValue($dvt.'5', 'Định mức');

            $objPHPExcel->getActiveSheet()->mergeCells($g.'4:'.$dvt.'4');


            if ($works) {



                $hang = 6;

                $i=1;


                $k=0;
                foreach ($works as $row) {

                    $details = $work_detail_model->getAllWork(array('where'=>'work_plan = '.$row->work_plan_id));

                    $objPHPExcel->setActiveSheetIndex(0)

                            ->setCellValue('A' . $hang, $i++)

                            ->setCellValueExplicit('B' . $hang, $row->work_plan_name)

                            ->setCellValue('C' . $hang, $row->work_plan_number)

                            ->setCellValue('D' . $hang, date('d/m/Y',$row->start_date))

                            ->setCellValue('E' . $hang, ($row->start_date!=$row->end_date?date('d/m/Y',$row->end_date):null));

                    $dvt = "F";
                    $min = 0;
                            foreach ($details as $detail) {

                                $objPHPExcel->setActiveSheetIndex($index_worksheet)

                                ->setCellValue($dvt . $hang, $detail->work_plan_detail);

                                $dvt++;

                                $min++;
                            }
                        if ($min<$max) {
                            for ($mi=$min; $mi<$max; $mi++) {
                                $objPHPExcel->setActiveSheetIndex($index_worksheet)

                                ->setCellValue($dvt . $hang, "");

                                $dvt++;
                            }
                        }

                     $str = "";
                      if($row->work_plan_owner != ""){
                          $contributors = explode(',', $row->work_plan_owner);
                          foreach ($contributors as $key) {
                            $pieces = explode(' ', $staff_data['name'][$key]);
                            $last_word = array_pop($pieces);
                            if ($str == "") {
                              $str = $last_word;
                            }
                            else{
                              $str .= ",".$last_word;
                            }
                          }
                      }

                      $staff = $staff_model->getStaffByWhere(array('account'=>$row->create_user));

                      $objPHPExcel->setActiveSheetIndex(0)

                            ->setCellValue(($dvt++) . $hang, ($staff->staff_id!=$row->work_plan_owner?$staff->staff_name:null))
                            ->setCellValue(($dvt++) . $hang, $str)
                            ->setCellValue(($dvt++) . $hang, $row->work_plan_comment)
                            ->setCellValue(($dvt++) . $hang, ($row->work_plan_complete==1?'x':null))
                            ->setCellValue($dvt . $hang, $row->work_plan_point);



                     $hang++; 

                }

            }

            $last = $dvt;
            $last--;

            $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, 'Tổng thời gian làm việc')
                        ->setCellValue($last . $hang, '=SUMPRODUCT(C6:C'.($hang-1).','.$last.'6:'.$last.($hang-1).')');

            $objPHPExcel->getActiveSheet()->mergeCells('A'.$hang.':C'.$hang);
            $objPHPExcel->getActiveSheet()->getStyle('A'.$hang.':'.$last.$hang)->getFont()->setBold(true);


            $objPHPExcel->getActiveSheet()->getStyle('A4:'.$last.$hang)->applyFromArray(

                array(

                    

                    'borders' => array(

                        'allborders' => array(

                          'style' => PHPExcel_Style_Border::BORDER_THIN

                        )

                    )

                )

            );



            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();



            $highestRow ++;



            $objPHPExcel->getActiveSheet()->mergeCells('A2:'.$last.'2');
            $objPHPExcel->getActiveSheet()->mergeCells('A4:A5');
            $objPHPExcel->getActiveSheet()->mergeCells('B4:B5');
            $objPHPExcel->getActiveSheet()->mergeCells('C4:C5');



            $objPHPExcel->getActiveSheet()->getStyle('A1:'.$last.$highestRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A1:'.$last.$highestRow)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


            $objPHPExcel->getActiveSheet()->getStyle('A1:'.$last.'4')->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(14);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(5);


            $objPHPExcel->getActiveSheet()->getStyle('A1:'.$last.$highestRow)->getFont()->setName('Times New Roman');
            $objPHPExcel->getActiveSheet()->getStyle('A1:'.$last.$highestRow)->getFont()->setSize(12);

            $objPHPExcel->getActiveSheet()->getStyle("A2")->getFont()->setSize(16);



            // Set properties

            $objPHPExcel->getProperties()->setCreator("VT")

                            ->setTitle("Sale Report")

                            ->setSubject("Sale Report")

                            ->setDescription("Sale Report.")

                            ->setKeywords("Sale Report")

                            ->setCategory("Sale Report");

            $objPHPExcel->getActiveSheet()->setTitle("Planning");



            $objPHPExcel->getActiveSheet()->freezePane('A6');

            $objPHPExcel->setActiveSheetIndex(0);







            



            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');



            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");

            header("Content-Disposition: attachment; filename= WEEKLY PLAN.xlsx");

            header("Cache-Control: max-age=0");

            ob_clean();

            //$objWriter->save("php://output");

            $tempDir = "public/files/"; 

            $objWriter->save($tempDir.'WEEKLY PLAN.xlsx');
        
            

            $staff_s = $staff_model->getStaff($sti);

            if ($staff_s->staff_email != "") {
                // Khai báo tạo PHPMailer
                $mail = $mail;
                //Khai báo gửi mail bằng SMTP
                $mail->IsSMTP();
                //Tắt mở kiểm tra lỗi trả về, chấp nhận các giá trị 0 1 2
                // 0 = off không thông báo bất kì gì, tốt nhất nên dùng khi đã hoàn thành.
                // 1 = Thông báo lỗi ở client
                // 2 = Thông báo lỗi cả client và lỗi ở server
                $mail->SMTPDebug  = 0;
                 
                $mail->Debugoutput = "html"; // Lỗi trả về hiển thị với cấu trúc HTML
                $mail->Host       = "smtp.zoho.com"; //host smtp để gửi mail
                $mail->Port       = 587; // cổng để gửi mail
                $mail->SMTPSecure = "tls"; //Phương thức mã hóa thư - ssl hoặc tls
                $mail->SMTPAuth   = true; //Xác thực SMTP
                $mail->CharSet = 'UTF-8';
                $mail->Username   = "lopxe@viet-trade.org"; // Tên đăng nhập tài khoản Gmail
                $mail->Password   = "lopxe!@#$"; //Mật khẩu của gmail
                $mail->SetFrom('lopxe@viet-trade.org', "VIET TRADE"); // Thông tin người gửi
                //$mail->AddReplyTo("sale@cmglogistics.com.vn","Sale CMG");// Ấn định email sẽ nhận khi người dùng reply lại.
                $mail->ClearAllRecipients(); // clear all
                $mail->AddAddress($staff_s->staff_email, $staff_s->staff_name);//Email của người nhận
                $mail->Subject = 'KẾ HOẠCH LÀM VIỆC'; //Tiêu đề của thư
                $mail->IsHTML(true); // send as HTML   
                //$mail->AddEmbeddedImage('public/img/christmas.jpg', 'hinhanh');
                $tre = "";
                $homnay = "";
                foreach ($works as $w) {
                    if($w->end_date < $batdau){
                        $tre .= '<p> - '.$w->work_plan_name.' | Deadline: '.date('d/m/Y',$w->end_date).'</p>';
                    }
                    else{
                        $homnay .= '<p> - '.$w->work_plan_name.' | Deadline: '.date('d/m/Y',$w->end_date).'</p>';
                    }
                }
                $noidung = '<p>Dear <strong>'.$staff_s->staff_name.',</strong></p>
                            <p><strong>Các công việc trễ deadline: </strong></p>
                            '.$tre.'
                            <p><strong>Các công việc phải làm hôm nay: </strong></p>
                            '.$homnay.'
                            <p>Vui lòng kiểm tra kế hoạch làm việc trong file đính kèm.</p>
                            <div style="color: rgb(0, 0, 0); font-family: Verdana, arial, Helvetica, sans-serif; background-image: initial; background-attachment: initial;background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">
                                <p class="MsoNormal" style="background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">
                                    &nbsp;</p>
                            </div>
                            <div align="center" class="MsoNormal" style="text-align: center; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial;background-clip: initial; background-position: initial; background-repeat: initial;">
                                <hr align="center" noshade="noshade" size="1" style="color:#CCCCCC" width="100%" />
                            </div>
                            <table border="0" cellpadding="0" cellspacing="0" class="MsoNormalTable" style="width: 100%; border-collapse: collapse; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;" width="100%">
                                <tbody>
                                    <tr>
                                        <td style="width:131.25pt;padding:0in 0in 0in 0in" valign="top" width="175">
                                            <p class="MsoNormal">
                            <!--[if gte vml 1]><v:shapetype  id="_x0000_t75" coordsize="21600,21600" o:spt="75" o:preferrelative="t" path="m@4@5l@4@11@9@11@9@5xe" filled="f" stroked="f"><v:stroke joinstyle="miter"/><v:formulas><v:f eqn="if lineDrawn pixelLineWidth 0"/><v:f eqn="sum @0 1 0"/><v:f eqn="sum 0 0 @1"/><v:f eqn="prod @2 1 2"/><v:f eqn="prod @3 21600 pixelWidth"/><v:f eqn="prod @3 21600 pixelHeight"/><v:f eqn="sum @0 0 1"/><v:f eqn="prod @6 1 2"/><v:f eqn="prod @7 21600 pixelWidth"/><v:f eqn="sum @8 21600 0"/><v:f eqn="prod @7 21600 pixelHeight"/><v:f eqn="sum @10 21600 0"/></v:formulas><v:path o:extrusionok="f" gradientshapeok="t" o:connecttype="rect"/><o:lock v:ext="edit" aspectratio="t"/></v:shapetype><v:shape id="Picture_x0020_1" o:spid="_x0000_i1027" type="#_x0000_t75" style="width:151.5pt;height:45.75pt;visibility:visible;mso-wrap-style:square"><v:imagedata src=http://viet-trade.org/public/images/1.png o:title=""/></v:shape><![endif]--><!--[if !vml]-->                  <span new="" style="font-size: 12pt; font-family: " times=""><img height="61" src="http://viet-trade.org/public/images/1.png" v:shapes="Picture_x0020_1" width="202" /><!--[endif]--><o:p></o:p></span></p>
                                        </td>
                                        <td style="padding:0in 0in 0in 0in">
                                            <p class="MsoNormal">
                                                <b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">Viet Trade Company Limited</span></b><span style="font-size: 10pt; font-family: Arial, sans-serif;">&nbsp;<br />
                                                </span><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">No.29, 51 Highway, Phuoc Tan ward, Bien Hoa city, Dong Nai province, Vietnam.<br />
                                                Tel: +84 (61) 3 937 607 / 747 - Fax: +84 (61) 3 937 677&nbsp;</span><span style="font-size: 10pt; font-family: Arial, sans-serif;"><br />
                                                </span><b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">Website:</span></b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">&nbsp;</span><a href="www.viet-trade.org"><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:blue">www.viet-trade.org</span></a><span new="" style="font-size: 12pt; font-family: " times=""><o:p></o:p></span></p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div align="center" class="MsoNormal" style="text-align:center">
                                <hr align="center" noshade="noshade" size="1" style="color:#CCCCCC" width="100%" />
                            </div>
                            <p style="color: rgb(0, 0, 0); font-family: Verdana, arial, Helvetica, sans-serif;">
                                <b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:black;mso-no-proof:yes">&ldquo;NH&Agrave;&nbsp;NHẬP KHẨU&nbsp;V&Agrave;&nbsp;PH&Acirc;N PHỐI TRỰC TIẾP&nbsp;</span></b><b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:red;mso-no-proof:yes">LỐP XE BỐ KẼM </span></b><b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:black;mso-no-proof:yes">CAO CẤP&nbsp;(Gi&aacute; rẻ nhất thị trường)&rdquo;</span></b></p>
                            <p class="MsoNormal">
                                &nbsp;</p>
                            <p class="MsoNormal">
                                <i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: black; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">*** Đặt h&agrave;ng v&agrave; nhận gi&aacute; ưu đ&atilde;i nhất h&atilde;y li&ecirc;n hệ: </span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: red; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">0931 557 775 </span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: black; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">- </span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: red; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">0931 55 99 09</span></i><i><span style="font-size:9.0pt;font-family:&quot;Verdana&quot;,sans-serif;color:black"><br />
                                <span style="background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">*** Email: </span></span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;"><a href="mailto:carl@viet-trade.org">carl@viet-trade.org</a> <span style="color:black">- </span><a href="mailto:it@viet-trade.org">it@viet-trade.org</a></span><o:p></o:p></i></p>
                            ';

                $mail->MsgHTML($noidung); //Nội dung của bức thư.
                // $mail->MsgHTML(file_get_contents("email-template.html"), dirname(__FILE__));
                // Gửi thư với tập tin html

                $mail->AltBody = 'KẾ HOẠCH LÀM VIỆC';//Nội dung rút gọn hiển thị bên ngoài thư mục thư.
                $mail->AddAttachment($tempDir.'WEEKLY PLAN.xlsx');//Tập tin cần attach
                // For most clients expecting the Priority header:
                // 1 = High, 2 = Medium, 3 = Low
                $mail->Priority = 1;
                // MS Outlook custom header
                // May set to "Urgent" or "Highest" rather than "High"
                $mail->AddCustomHeader("X-MSMail-Priority: High");
                // Not sure if Priority will also set the Importance header:
                $mail->AddCustomHeader("Importance: High"); 

                if($mail->Send()){
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                    $filename = "action_logs.txt";

                    $text = date('d/m/Y H:i:s')."|BOOT|sendmail|".$staff_s->staff_email."\n"."\r\n";

                    

                    $fh = fopen($filename, "a") or die("Could not open log file.");

                    fwrite($fh, $text) or die("Could not write file!");

                    fclose($fh);
                }
            }

            unlink($tempDir.'WEEKLY PLAN.xlsx');

            
    }
    function exportweek($mail,$objPHPExcel,$staff_model,$work_plan_model,$detail_model,$bd,$kt){

        $this->view->disableLayout();

        $batdau = $bd;
        $ketthuc = $kt;

        $staff_model = $staff_model;
        $work_model = $work_plan_model;
        $work_detail_model = $detail_model;
        
        $staffs = $staff_model->getAllStaff(array('where'=>'status=1'));
        $staff_data = array();
        foreach ($staffs as $st) {
            $staff_data['name'][$st->staff_id] = $st->staff_name;
        }

        $objPHPExcel = $objPHPExcel;

        $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)

        $staff_alls = $staff_model->getAllStaff(array('where'=>'staff_id IN (SELECT work_plan_owner FROM work_plan)'));
        foreach ($staff_alls as $stf) {
            $data = array(
                'order_by'=>'start_date',
                'order'=>'ASC',
                'where' => 'work_plan_owner = '.$stf->staff_id.' AND ((work_plan_complete != 1 AND start_date <= '.$ketthuc.') OR (work_plan_complete=1 AND work_plan_complete_date > end_date AND work_plan_complete_date >= '.$batdau.' AND work_plan_complete_date <= '.$ketthuc.') OR (start_date >= '.$batdau.' AND end_date <= '.$ketthuc.') )',
                );

            

            $works = $work_model->getAllWork($data);

            

            
            if($index_worksheet>0){
                $objPHPExcel->createSheet();
            }

            $objPHPExcel->setActiveSheetIndex($index_worksheet);
                

                $objPHPExcel->setActiveSheetIndex($index_worksheet)

                    ->setCellValue('A2', 'KẾ HOẠCH LÀM VIỆC')

                    ->setCellValue('A4', 'STT')

                   ->setCellValue('B4', 'Tên công việc')

                   ->setCellValue('C4', 'SL')

                   ->setCellValue('D4', 'Thời gian')

                   ->setCellValue('F4', 'Chi tiết')

                   ->setCellValue('D5', 'Từ ngày')

                   ->setCellValue('E5', 'Đến ngày');

                
                $numbers = $work_model->queryWork('SELECT max(work_plan_number) AS num FROM work_plan WHERE (work_plan_complete != 1 OR start_date >= '.$batdau.' ) AND work_plan_owner = '.$stf->staff_id);
                foreach ($numbers as $key) {
                    $max = $key->num;
                }

                $dvt = "E";
                for ($i=1; $i<=$max; $i++) {
                    $dvt++;
                    $objPHPExcel->setActiveSheetIndex($index_worksheet)->setCellValue($dvt.'5', $i);
                }


                $objPHPExcel->getActiveSheet()->mergeCells('D4:E4');
                $objPHPExcel->getActiveSheet()->mergeCells('F4:'.($dvt++).'4');

                $g = $dvt;

               $objPHPExcel->setActiveSheetIndex($index_worksheet)

               ->setCellValue($dvt.'4', 'PIC')
               ->setCellValue(($dvt++).'5', 'Giao');

               $objPHPExcel->getActiveSheet()->mergeCells($g.'4:'.$dvt.'4');

               $objPHPExcel->setActiveSheetIndex($index_worksheet)

                   ->setCellValue(($dvt++).'5', 'Thực hiện')
                   ->setCellValue($dvt.'4', 'Ghi chú');

                $g = $dvt;

                $objPHPExcel->setActiveSheetIndex($index_worksheet)

                   ->setCellValue(($dvt++).'5', 'Note')
                   ->setCellValue(($dvt++).'5', 'Hoàn thành')
                   ->setCellValue($dvt.'5', 'Định mức');

                $objPHPExcel->getActiveSheet()->mergeCells($g.'4:'.$dvt.'4');


                if ($works) {



                    $hang = 6;

                    $i=1;


                    $k=0;
                    foreach ($works as $row) {

                        $details = $work_detail_model->getAllWork(array('where'=>'work_plan = '.$row->work_plan_id));

                        $objPHPExcel->setActiveSheetIndex($index_worksheet)

                                ->setCellValue('A' . $hang, $i++)

                                ->setCellValueExplicit('B' . $hang, $row->work_plan_name)

                                ->setCellValue('C' . $hang, $row->work_plan_number)

                                ->setCellValue('D' . $hang, date('d/m/Y',$row->start_date))

                                ->setCellValue('E' . $hang, ($row->start_date!=$row->end_date?date('d/m/Y',$row->end_date):null));

                        $dvt = "F";
                        $min = 0;
                            foreach ($details as $detail) {

                                $objPHPExcel->setActiveSheetIndex($index_worksheet)

                                ->setCellValue($dvt . $hang, $detail->work_plan_detail);

                                $dvt++;

                                $min++;
                            }
                        if ($min<$max) {
                            for ($mi=$min; $mi<$max; $mi++) {
                                $objPHPExcel->setActiveSheetIndex($index_worksheet)

                                ->setCellValue($dvt . $hang, "");

                                $dvt++;
                            }
                        }

                         $str = "";
                          if($row->work_plan_owner != ""){
                              $contributors = explode(',', $row->work_plan_owner);
                              foreach ($contributors as $key) {
                                $pieces = explode(' ', $staff_data['name'][$key]);
                                $last_word = array_pop($pieces);
                                if ($str == "") {
                                  $str = $last_word;
                                }
                                else{
                                  $str .= ",".$last_word;
                                }
                              }
                          }

                          $staff = $staff_model->getStaffByWhere(array('account'=>$row->create_user));

                          $objPHPExcel->setActiveSheetIndex($index_worksheet)

                                ->setCellValue(($dvt++) . $hang, ($staff->staff_id!=$row->work_plan_owner?$staff->staff_name:null))
                                ->setCellValue(($dvt++) . $hang, $str)
                                ->setCellValue(($dvt++) . $hang, $row->work_plan_comment)
                                ->setCellValue(($dvt++) . $hang, ($row->work_plan_complete==1?'x':null))
                                ->setCellValue($dvt . $hang, $row->work_plan_point);



                         $hang++; 

                    }

                }

                $last = $dvt;
                $last--;

                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, 'Tổng thời gian làm việc')
                        ->setCellValue($last . $hang, '=SUMPRODUCT(C6:C'.($hang-1).','.$last.'6:'.$last.($hang-1).')');

                $objPHPExcel->getActiveSheet()->mergeCells('A'.$hang.':C'.$hang);
                $objPHPExcel->getActiveSheet()->getStyle('A'.$hang.':'.$last.$hang)->getFont()->setBold(true);


                $objPHPExcel->getActiveSheet()->getStyle('A4:'.$last.$hang)->applyFromArray(

                    array(

                        

                        'borders' => array(

                            'allborders' => array(

                              'style' => PHPExcel_Style_Border::BORDER_THIN

                            )

                        )

                    )

                );



                $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();



                $highestRow ++;



                $objPHPExcel->getActiveSheet()->mergeCells('A2:'.$last.'2');
                $objPHPExcel->getActiveSheet()->mergeCells('A4:A5');
                $objPHPExcel->getActiveSheet()->mergeCells('B4:B5');
                $objPHPExcel->getActiveSheet()->mergeCells('C4:C5');



                $objPHPExcel->getActiveSheet()->getStyle('A1:'.$last.$highestRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                $objPHPExcel->getActiveSheet()->getStyle('A1:'.$last.$highestRow)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


                $objPHPExcel->getActiveSheet()->getStyle('A1:'.$last.'4')->getFont()->setBold(true);

                $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(14);

                $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
                $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(5);


                $objPHPExcel->getActiveSheet()->getStyle('A1:'.$last.$highestRow)->getFont()->setName('Times New Roman');
                $objPHPExcel->getActiveSheet()->getStyle('A1:'.$last.$highestRow)->getFont()->setSize(12);

                $objPHPExcel->getActiveSheet()->getStyle("A2")->getFont()->setSize(16);



                

                $objPHPExcel->getActiveSheet()->setTitle($stf->staff_name);



                $objPHPExcel->getActiveSheet()->freezePane('A6');

                

                $index_worksheet++;
                
        }
        

        // Set properties

                $objPHPExcel->getProperties()->setCreator("VT")

                                ->setTitle("Sale Report")

                                ->setSubject("Sale Report")

                                ->setDescription("Sale Report.")

                                ->setKeywords("Sale Report")

                                ->setCategory("Sale Report");
            
                $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');



                header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");

                header("Content-Disposition: attachment; filename= BAO CAO TUAN.xlsx");

                header("Cache-Control: max-age=0");

                ob_clean();

                //$objWriter->save("php://output");

                $tempDir = "public/files/"; 

                $objWriter->save($tempDir.'BAO CAO TUAN.xlsx');

                // Khai báo tạo PHPMailer
                $mail = $mail;
                //Khai báo gửi mail bằng SMTP
                $mail->IsSMTP();
                //Tắt mở kiểm tra lỗi trả về, chấp nhận các giá trị 0 1 2
                // 0 = off không thông báo bất kì gì, tốt nhất nên dùng khi đã hoàn thành.
                // 1 = Thông báo lỗi ở client
                // 2 = Thông báo lỗi cả client và lỗi ở server
                $mail->SMTPDebug  = 0;
                 
                $mail->Debugoutput = "html"; // Lỗi trả về hiển thị với cấu trúc HTML
                $mail->Host       = "smtp.zoho.com"; //host smtp để gửi mail
                $mail->Port       = 587; // cổng để gửi mail
                $mail->SMTPSecure = "tls"; //Phương thức mã hóa thư - ssl hoặc tls
                $mail->SMTPAuth   = true; //Xác thực SMTP
                $mail->CharSet = 'UTF-8';
                $mail->Username   = "lopxe@viet-trade.org"; // Tên đăng nhập tài khoản Gmail
                $mail->Password   = "lopxe!@#$"; //Mật khẩu của gmail
                $mail->SetFrom('lopxe@viet-trade.org', "VIET TRADE"); // Thông tin người gửi
                //$mail->AddReplyTo("sale@cmglogistics.com.vn","Sale CMG");// Ấn định email sẽ nhận khi người dùng reply lại.
                $mail->ClearAllRecipients(); // clear all
                $mail->AddAddress('karl@caimeptrading.com', 'Karl');//Email của người nhận
                $mail->AddAddress('chdong@viet-trade.org', 'Cao Huy Dong');//Email của người nhận
                $mail->AddAddress('itcmg@cmglogs.com', 'IT');//Email của người nhận
                $mail->Subject = 'BAO CAO TUAN'; //Tiêu đề của thư
                $mail->IsHTML(true); // send as HTML   
                //$mail->AddEmbeddedImage('public/img/christmas.jpg', 'hinhanh');
                $noidung = '<p>Kính gửi <strong>BGĐ,</strong></p>
                            <p>Bảng báo cáo kế hoạch làm việc tuần trong file đính kèm.</p>
                            <div style="color: rgb(0, 0, 0); font-family: Verdana, arial, Helvetica, sans-serif; background-image: initial; background-attachment: initial;background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">
                                <p class="MsoNormal" style="background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">
                                    &nbsp;</p>
                            </div>
                            <div align="center" class="MsoNormal" style="text-align: center; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial;background-clip: initial; background-position: initial; background-repeat: initial;">
                                <hr align="center" noshade="noshade" size="1" style="color:#CCCCCC" width="100%" />
                            </div>
                            <table border="0" cellpadding="0" cellspacing="0" class="MsoNormalTable" style="width: 100%; border-collapse: collapse; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;" width="100%">
                                <tbody>
                                    <tr>
                                        <td style="width:131.25pt;padding:0in 0in 0in 0in" valign="top" width="175">
                                            <p class="MsoNormal">
                            <!--[if gte vml 1]><v:shapetype  id="_x0000_t75" coordsize="21600,21600" o:spt="75" o:preferrelative="t" path="m@4@5l@4@11@9@11@9@5xe" filled="f" stroked="f"><v:stroke joinstyle="miter"/><v:formulas><v:f eqn="if lineDrawn pixelLineWidth 0"/><v:f eqn="sum @0 1 0"/><v:f eqn="sum 0 0 @1"/><v:f eqn="prod @2 1 2"/><v:f eqn="prod @3 21600 pixelWidth"/><v:f eqn="prod @3 21600 pixelHeight"/><v:f eqn="sum @0 0 1"/><v:f eqn="prod @6 1 2"/><v:f eqn="prod @7 21600 pixelWidth"/><v:f eqn="sum @8 21600 0"/><v:f eqn="prod @7 21600 pixelHeight"/><v:f eqn="sum @10 21600 0"/></v:formulas><v:path o:extrusionok="f" gradientshapeok="t" o:connecttype="rect"/><o:lock v:ext="edit" aspectratio="t"/></v:shapetype><v:shape id="Picture_x0020_1" o:spid="_x0000_i1027" type="#_x0000_t75" style="width:151.5pt;height:45.75pt;visibility:visible;mso-wrap-style:square"><v:imagedata src=http://viet-trade.org/public/images/1.png o:title=""/></v:shape><![endif]--><!--[if !vml]-->                  <span new="" style="font-size: 12pt; font-family: " times=""><img height="61" src="http://viet-trade.org/public/images/1.png" v:shapes="Picture_x0020_1" width="202" /><!--[endif]--><o:p></o:p></span></p>
                                        </td>
                                        <td style="padding:0in 0in 0in 0in">
                                            <p class="MsoNormal">
                                                <b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">Viet Trade Company Limited</span></b><span style="font-size: 10pt; font-family: Arial, sans-serif;">&nbsp;<br />
                                                </span><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">No.29, 51 Highway, Phuoc Tan ward, Bien Hoa city, Dong Nai province, Vietnam.<br />
                                                Tel: +84 (61) 3 937 607 / 747 - Fax: +84 (61) 3 937 677&nbsp;</span><span style="font-size: 10pt; font-family: Arial, sans-serif;"><br />
                                                </span><b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">Website:</span></b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">&nbsp;</span><a href="www.viet-trade.org"><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:blue">www.viet-trade.org</span></a><span new="" style="font-size: 12pt; font-family: " times=""><o:p></o:p></span></p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div align="center" class="MsoNormal" style="text-align:center">
                                <hr align="center" noshade="noshade" size="1" style="color:#CCCCCC" width="100%" />
                            </div>
                            <p style="color: rgb(0, 0, 0); font-family: Verdana, arial, Helvetica, sans-serif;">
                                <b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:black;mso-no-proof:yes">&ldquo;NH&Agrave;&nbsp;NHẬP KHẨU&nbsp;V&Agrave;&nbsp;PH&Acirc;N PHỐI TRỰC TIẾP&nbsp;</span></b><b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:red;mso-no-proof:yes">LỐP XE BỐ KẼM </span></b><b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:black;mso-no-proof:yes">CAO CẤP&nbsp;(Gi&aacute; rẻ nhất thị trường)&rdquo;</span></b></p>
                            <p class="MsoNormal">
                                &nbsp;</p>
                            <p class="MsoNormal">
                                <i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: black; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">*** Đặt h&agrave;ng v&agrave; nhận gi&aacute; ưu đ&atilde;i nhất h&atilde;y li&ecirc;n hệ: </span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: red; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">0931 557 775 </span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: black; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">- </span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: red; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">0931 55 99 09</span></i><i><span style="font-size:9.0pt;font-family:&quot;Verdana&quot;,sans-serif;color:black"><br />
                                <span style="background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">*** Email: </span></span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;"><a href="mailto:carl@viet-trade.org">carl@viet-trade.org</a> <span style="color:black">- </span><a href="mailto:it@viet-trade.org">it@viet-trade.org</a></span><o:p></o:p></i></p>
                            ';

                $mail->MsgHTML($noidung); //Nội dung của bức thư.
                // $mail->MsgHTML(file_get_contents("email-template.html"), dirname(__FILE__));
                // Gửi thư với tập tin html

                $mail->AddAttachment($tempDir.'BAO CAO TUAN.xlsx');//Tập tin cần attach

                $mail->AltBody = 'BÁO CÁO TUẦN';//Nội dung rút gọn hiển thị bên ngoài thư mục thư.
                
                // For most clients expecting the Priority header:
                // 1 = High, 2 = Medium, 3 = Low
                $mail->Priority = 1;
                // MS Outlook custom header
                // May set to "Urgent" or "Highest" rather than "High"
                $mail->AddCustomHeader("X-MSMail-Priority: High");
                // Not sure if Priority will also set the Importance header:
                $mail->AddCustomHeader("Importance: High"); 

                if($mail->Send()){
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                    $filename = "action_logs.txt";

                    $text = date('d/m/Y H:i:s')."|BOOT|sendmail|Admin"."\n"."\r\n";

                    

                    $fh = fopen($filename, "a") or die("Could not open log file.");

                    fwrite($fh, $text) or die("Could not write file!");

                    fclose($fh);
              
                }
            
                unlink($tempDir.'BAO CAO TUAN.xlsx');
            

            

            
    }

    function exportplanweek($mail,$objPHPExcel,$staff_model,$work_plan_model,$detail_model,$bd,$kt){

        $this->view->disableLayout();

        $batdau = $bd;
        $ketthuc = $kt;

        $staff_model = $staff_model;
        $work_model = $work_plan_model;
        $work_detail_model = $detail_model;
        
        $staffs = $staff_model->getAllStaff(array('where'=>'status=1'));
        $staff_data = array();
        foreach ($staffs as $st) {
            $staff_data['name'][$st->staff_id] = $st->staff_name;
        }

        $objPHPExcel = $objPHPExcel;

        $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)

        $staff_alls = $staff_model->getAllStaff(array('where'=>'staff_id IN (SELECT work_plan_owner FROM work_plan)'));
        foreach ($staff_alls as $stf) {
            $data = array(
                'order_by'=>'start_date',
                'order'=>'ASC',
                'where' => 'work_plan_owner = '.$stf->staff_id.' AND  (start_date >= '.$batdau.' AND end_date <= '.$ketthuc.') )',
                );

            

            $works = $work_model->getAllWork($data);

            

            
            if($index_worksheet>0){
                $objPHPExcel->createSheet();
            }

            $objPHPExcel->setActiveSheetIndex($index_worksheet);
                

                $objPHPExcel->setActiveSheetIndex($index_worksheet)

                    ->setCellValue('A2', 'KẾ HOẠCH LÀM VIỆC')

                    ->setCellValue('A4', 'STT')

                   ->setCellValue('B4', 'Tên công việc')

                   ->setCellValue('C4', 'SL')

                   ->setCellValue('D4', 'Thời gian')

                   ->setCellValue('F4', 'Chi tiết')

                   ->setCellValue('D5', 'Từ ngày')

                   ->setCellValue('E5', 'Đến ngày');

                
                $numbers = $work_model->queryWork('SELECT max(work_plan_number) AS num FROM work_plan WHERE (work_plan_complete != 1 OR start_date >= '.$batdau.' ) AND work_plan_owner = '.$stf->staff_id);
                foreach ($numbers as $key) {
                    $max = $key->num;
                }

                $dvt = "E";
                for ($i=1; $i<=$max; $i++) {
                    $dvt++;
                    $objPHPExcel->setActiveSheetIndex($index_worksheet)->setCellValue($dvt.'5', $i);
                }


                $objPHPExcel->getActiveSheet()->mergeCells('D4:E4');
                $objPHPExcel->getActiveSheet()->mergeCells('F4:'.($dvt++).'4');

                $g = $dvt;

               $objPHPExcel->setActiveSheetIndex($index_worksheet)

               ->setCellValue($dvt.'4', 'PIC')
               ->setCellValue(($dvt++).'5', 'Giao');

               $objPHPExcel->getActiveSheet()->mergeCells($g.'4:'.$dvt.'4');

               $objPHPExcel->setActiveSheetIndex($index_worksheet)

                   ->setCellValue(($dvt++).'5', 'Thực hiện')
                   ->setCellValue($dvt.'4', 'Ghi chú');

                $g = $dvt;

                $objPHPExcel->setActiveSheetIndex($index_worksheet)

                   ->setCellValue(($dvt++).'5', 'Note')
                   ->setCellValue(($dvt++).'5', 'Hoàn thành')
                   ->setCellValue($dvt.'5', 'Định mức');

                $objPHPExcel->getActiveSheet()->mergeCells($g.'4:'.$dvt.'4');


                if ($works) {



                    $hang = 6;

                    $i=1;


                    $k=0;
                    foreach ($works as $row) {

                        $details = $work_detail_model->getAllWork(array('where'=>'work_plan = '.$row->work_plan_id));

                        $objPHPExcel->setActiveSheetIndex($index_worksheet)

                                ->setCellValue('A' . $hang, $i++)

                                ->setCellValueExplicit('B' . $hang, $row->work_plan_name)

                                ->setCellValue('C' . $hang, $row->work_plan_number)

                                ->setCellValue('D' . $hang, date('d/m/Y',$row->start_date))

                                ->setCellValue('E' . $hang, ($row->start_date!=$row->end_date?date('d/m/Y',$row->end_date):null));

                        $dvt = "F";
                        $min = 0;
                            foreach ($details as $detail) {

                                $objPHPExcel->setActiveSheetIndex($index_worksheet)

                                ->setCellValue($dvt . $hang, $detail->work_plan_detail);

                                $dvt++;

                                $min++;
                            }
                        if ($min<$max) {
                            for ($mi=$min; $mi<$max; $mi++) {
                                $objPHPExcel->setActiveSheetIndex($index_worksheet)

                                ->setCellValue($dvt . $hang, "");

                                $dvt++;
                            }
                        }

                         $str = "";
                          if($row->work_plan_owner != ""){
                              $contributors = explode(',', $row->work_plan_owner);
                              foreach ($contributors as $key) {
                                $pieces = explode(' ', $staff_data['name'][$key]);
                                $last_word = array_pop($pieces);
                                if ($str == "") {
                                  $str = $last_word;
                                }
                                else{
                                  $str .= ",".$last_word;
                                }
                              }
                          }

                          $staff = $staff_model->getStaffByWhere(array('account'=>$row->create_user));

                          $objPHPExcel->setActiveSheetIndex($index_worksheet)

                                ->setCellValue(($dvt++) . $hang, ($staff->staff_id!=$row->work_plan_owner?$staff->staff_name:null))
                                ->setCellValue(($dvt++) . $hang, $str)
                                ->setCellValue(($dvt++) . $hang, $row->work_plan_comment)
                                ->setCellValue(($dvt++) . $hang, ($row->work_plan_complete==1?'x':null))
                                ->setCellValue($dvt . $hang, $row->work_plan_point);



                         $hang++; 

                    }

                }

                $last = $dvt;
                $last--;

                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, 'Tổng thời gian làm việc')
                        ->setCellValue($last . $hang, '=SUMPRODUCT(C6:C'.($hang-1).','.$last.'6:'.$last.($hang-1).')');

                $objPHPExcel->getActiveSheet()->mergeCells('A'.$hang.':C'.$hang);
                $objPHPExcel->getActiveSheet()->getStyle('A'.$hang.':'.$last.$hang)->getFont()->setBold(true);



                $objPHPExcel->getActiveSheet()->getStyle('A4:'.$last.$hang)->applyFromArray(

                    array(

                        

                        'borders' => array(

                            'allborders' => array(

                              'style' => PHPExcel_Style_Border::BORDER_THIN

                            )

                        )

                    )

                );



                $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();



                $highestRow ++;



                $objPHPExcel->getActiveSheet()->mergeCells('A2:'.$last.'2');
                $objPHPExcel->getActiveSheet()->mergeCells('A4:A5');
                $objPHPExcel->getActiveSheet()->mergeCells('B4:B5');
                $objPHPExcel->getActiveSheet()->mergeCells('C4:C5');



                $objPHPExcel->getActiveSheet()->getStyle('A1:'.$last.$highestRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                $objPHPExcel->getActiveSheet()->getStyle('A1:'.$last.$highestRow)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


                $objPHPExcel->getActiveSheet()->getStyle('A1:'.$last.'4')->getFont()->setBold(true);

                $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(14);

                $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
                $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(5);


                $objPHPExcel->getActiveSheet()->getStyle('A1:'.$last.$highestRow)->getFont()->setName('Times New Roman');
                $objPHPExcel->getActiveSheet()->getStyle('A1:'.$last.$highestRow)->getFont()->setSize(12);

                $objPHPExcel->getActiveSheet()->getStyle("A2")->getFont()->setSize(16);



                

                $objPHPExcel->getActiveSheet()->setTitle($stf->staff_name);



                $objPHPExcel->getActiveSheet()->freezePane('A6');

                

                $index_worksheet++;
                
        }
        

        // Set properties

                $objPHPExcel->getProperties()->setCreator("VT")

                                ->setTitle("Sale Report")

                                ->setSubject("Sale Report")

                                ->setDescription("Sale Report.")

                                ->setKeywords("Sale Report")

                                ->setCategory("Sale Report");
            
                $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');



                header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");

                header("Content-Disposition: attachment; filename= KE HOACH TUAN.xlsx");

                header("Cache-Control: max-age=0");

                ob_clean();

                //$objWriter->save("php://output");

                $tempDir = "public/files/"; 

                $objWriter->save($tempDir.'KE HOACH TUAN.xlsx');

                // Khai báo tạo PHPMailer
                $mail = $mail;
                //Khai báo gửi mail bằng SMTP
                $mail->IsSMTP();
                //Tắt mở kiểm tra lỗi trả về, chấp nhận các giá trị 0 1 2
                // 0 = off không thông báo bất kì gì, tốt nhất nên dùng khi đã hoàn thành.
                // 1 = Thông báo lỗi ở client
                // 2 = Thông báo lỗi cả client và lỗi ở server
                $mail->SMTPDebug  = 0;
                 
                $mail->Debugoutput = "html"; // Lỗi trả về hiển thị với cấu trúc HTML
                $mail->Host       = "smtp.zoho.com"; //host smtp để gửi mail
                $mail->Port       = 587; // cổng để gửi mail
                $mail->SMTPSecure = "tls"; //Phương thức mã hóa thư - ssl hoặc tls
                $mail->SMTPAuth   = true; //Xác thực SMTP
                $mail->CharSet = 'UTF-8';
                $mail->Username   = "lopxe@viet-trade.org"; // Tên đăng nhập tài khoản Gmail
                $mail->Password   = "lopxe!@#$"; //Mật khẩu của gmail
                $mail->SetFrom('lopxe@viet-trade.org', "VIET TRADE"); // Thông tin người gửi
                //$mail->AddReplyTo("sale@cmglogistics.com.vn","Sale CMG");// Ấn định email sẽ nhận khi người dùng reply lại.
                $mail->ClearAllRecipients(); // clear all
                $mail->AddAddress('karl@caimeptrading.com', 'Karl');//Email của người nhận
                $mail->AddAddress('chdong@viet-trade.org', 'Cao Huy Dong');//Email của người nhận
                $mail->AddAddress('itcmg@cmglogs.com', 'IT');//Email của người nhận
                $mail->Subject = 'KE HOACH TUAN'; //Tiêu đề của thư
                $mail->IsHTML(true); // send as HTML   
                //$mail->AddEmbeddedImage('public/img/christmas.jpg', 'hinhanh');
                $noidung = '<p>Kính gửi <strong>BGĐ,</strong></p>
                            <p>Bảng kế hoạch làm việc tuần trong file đính kèm.</p>
                            <div style="color: rgb(0, 0, 0); font-family: Verdana, arial, Helvetica, sans-serif; background-image: initial; background-attachment: initial;background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">
                                <p class="MsoNormal" style="background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">
                                    &nbsp;</p>
                            </div>
                            <div align="center" class="MsoNormal" style="text-align: center; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial;background-clip: initial; background-position: initial; background-repeat: initial;">
                                <hr align="center" noshade="noshade" size="1" style="color:#CCCCCC" width="100%" />
                            </div>
                            <table border="0" cellpadding="0" cellspacing="0" class="MsoNormalTable" style="width: 100%; border-collapse: collapse; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;" width="100%">
                                <tbody>
                                    <tr>
                                        <td style="width:131.25pt;padding:0in 0in 0in 0in" valign="top" width="175">
                                            <p class="MsoNormal">
                            <!--[if gte vml 1]><v:shapetype  id="_x0000_t75" coordsize="21600,21600" o:spt="75" o:preferrelative="t" path="m@4@5l@4@11@9@11@9@5xe" filled="f" stroked="f"><v:stroke joinstyle="miter"/><v:formulas><v:f eqn="if lineDrawn pixelLineWidth 0"/><v:f eqn="sum @0 1 0"/><v:f eqn="sum 0 0 @1"/><v:f eqn="prod @2 1 2"/><v:f eqn="prod @3 21600 pixelWidth"/><v:f eqn="prod @3 21600 pixelHeight"/><v:f eqn="sum @0 0 1"/><v:f eqn="prod @6 1 2"/><v:f eqn="prod @7 21600 pixelWidth"/><v:f eqn="sum @8 21600 0"/><v:f eqn="prod @7 21600 pixelHeight"/><v:f eqn="sum @10 21600 0"/></v:formulas><v:path o:extrusionok="f" gradientshapeok="t" o:connecttype="rect"/><o:lock v:ext="edit" aspectratio="t"/></v:shapetype><v:shape id="Picture_x0020_1" o:spid="_x0000_i1027" type="#_x0000_t75" style="width:151.5pt;height:45.75pt;visibility:visible;mso-wrap-style:square"><v:imagedata src=http://viet-trade.org/public/images/1.png o:title=""/></v:shape><![endif]--><!--[if !vml]-->                  <span new="" style="font-size: 12pt; font-family: " times=""><img height="61" src="http://viet-trade.org/public/images/1.png" v:shapes="Picture_x0020_1" width="202" /><!--[endif]--><o:p></o:p></span></p>
                                        </td>
                                        <td style="padding:0in 0in 0in 0in">
                                            <p class="MsoNormal">
                                                <b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">Viet Trade Company Limited</span></b><span style="font-size: 10pt; font-family: Arial, sans-serif;">&nbsp;<br />
                                                </span><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">No.29, 51 Highway, Phuoc Tan ward, Bien Hoa city, Dong Nai province, Vietnam.<br />
                                                Tel: +84 (61) 3 937 607 / 747 - Fax: +84 (61) 3 937 677&nbsp;</span><span style="font-size: 10pt; font-family: Arial, sans-serif;"><br />
                                                </span><b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">Website:</span></b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">&nbsp;</span><a href="www.viet-trade.org"><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:blue">www.viet-trade.org</span></a><span new="" style="font-size: 12pt; font-family: " times=""><o:p></o:p></span></p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div align="center" class="MsoNormal" style="text-align:center">
                                <hr align="center" noshade="noshade" size="1" style="color:#CCCCCC" width="100%" />
                            </div>
                            <p style="color: rgb(0, 0, 0); font-family: Verdana, arial, Helvetica, sans-serif;">
                                <b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:black;mso-no-proof:yes">&ldquo;NH&Agrave;&nbsp;NHẬP KHẨU&nbsp;V&Agrave;&nbsp;PH&Acirc;N PHỐI TRỰC TIẾP&nbsp;</span></b><b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:red;mso-no-proof:yes">LỐP XE BỐ KẼM </span></b><b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:black;mso-no-proof:yes">CAO CẤP&nbsp;(Gi&aacute; rẻ nhất thị trường)&rdquo;</span></b></p>
                            <p class="MsoNormal">
                                &nbsp;</p>
                            <p class="MsoNormal">
                                <i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: black; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">*** Đặt h&agrave;ng v&agrave; nhận gi&aacute; ưu đ&atilde;i nhất h&atilde;y li&ecirc;n hệ: </span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: red; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">0931 557 775 </span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: black; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">- </span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; color: red; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">0931 55 99 09</span></i><i><span style="font-size:9.0pt;font-family:&quot;Verdana&quot;,sans-serif;color:black"><br />
                                <span style="background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">*** Email: </span></span></i><i><span style="font-size: 9pt; font-family: Verdana, sans-serif; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;"><a href="mailto:carl@viet-trade.org">carl@viet-trade.org</a> <span style="color:black">- </span><a href="mailto:it@viet-trade.org">it@viet-trade.org</a></span><o:p></o:p></i></p>
                            ';

                $mail->MsgHTML($noidung); //Nội dung của bức thư.
                // $mail->MsgHTML(file_get_contents("email-template.html"), dirname(__FILE__));
                // Gửi thư với tập tin html

                $mail->AddAttachment($tempDir.'KE HOACH TUAN.xlsx');//Tập tin cần attach

                $mail->AltBody = 'KẾ HOẠCH TUẦN';//Nội dung rút gọn hiển thị bên ngoài thư mục thư.
                
                // For most clients expecting the Priority header:
                // 1 = High, 2 = Medium, 3 = Low
                $mail->Priority = 1;
                // MS Outlook custom header
                // May set to "Urgent" or "Highest" rather than "High"
                $mail->AddCustomHeader("X-MSMail-Priority: High");
                // Not sure if Priority will also set the Importance header:
                $mail->AddCustomHeader("Importance: High"); 

                if($mail->Send()){
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                    $filename = "action_logs.txt";

                    $text = date('d/m/Y H:i:s')."|BOOT|sendmail|Admin"."\n"."\r\n";

                    

                    $fh = fopen($filename, "a") or die("Could not open log file.");

                    fwrite($fh, $text) or die("Could not write file!");

                    fclose($fh);
              
                }
            
                unlink($tempDir.'KE HOACH TUAN.xlsx');
            

            

            
    }

    public function sendmail(){
        $this->view->disableLayout();
        require "lib/class.phpmailer.php";
        require("lib/Classes/PHPExcel/IOFactory.php");
        require("lib/Classes/PHPExcel.php");
        $objPHPExcel = new PHPExcel();
        $mail = new PHPMailer();

        $work_plan_model = $this->model->get('workplanModel');
        $detail_model = $this->model->get('workplandetailModel');
        $staff_model = $this->model->get('staffModel');
        $staff_alls = $staff_model->getAllStaff(array('where'=>'staff_id IN (SELECT work_plan_owner FROM work_plan)'));

        $today = strtotime(date('d-m-Y'));
        foreach ($staff_alls as $s) {
            $this->exportstaff($mail,$objPHPExcel,$staff_model,$work_plan_model,$detail_model,$s->staff_id,$today,$today);

            sleep(10);
        }
    }
    public function sendmailweek(){
        $this->view->disableLayout();
        require "lib/class.phpmailer.php";
        require("lib/Classes/PHPExcel/IOFactory.php");
        require("lib/Classes/PHPExcel.php");
        $objPHPExcel = new PHPExcel();
        $mail = new PHPMailer();

        $work_plan_model = $this->model->get('workplanModel');
        $detail_model = $this->model->get('workplandetailModel');
        $staff_model = $this->model->get('staffModel');

        $batdau = strtotime(date( 'd-m-Y', strtotime( 'monday this week' ) ));
        $ketthuc = strtotime(date( 'd-m-Y', strtotime( 'saturday this week' ) ));
        
        $this->exportweek($mail,$objPHPExcel,$staff_model,$work_plan_model,$detail_model,$batdau,$ketthuc);

        sleep(10);

        $batdau = strtotime(date( 'd-m-Y', strtotime( 'next monday' ) ));
        $ketthuc = strtotime(date( 'd-m-Y', strtotime( 'next saturday' ) ));

        $this->exportplanweek($mail,$objPHPExcel,$staff_model,$work_plan_model,$detail_model,$batdau,$ketthuc);

    }

}
?>