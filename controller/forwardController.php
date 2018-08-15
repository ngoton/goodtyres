<?php
Class forwardController Extends baseController {
    public function index() {
    	$this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Kết chuyển cuối kỳ';

        $ketthuc = isset($_POST['ngaythang'])?$_POST['ngaythang']:date('t-m-Y');
        $ngayketthuc = date('t-m-Y');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $additional_date = strtotime(str_replace('/', '-', $_POST['additional_date']));
            $ketthuc = date('t-m-Y',$additional_date);
            $ngayketthuc = date('t-m-Y',$additional_date);
        }

        $this->view->data['ketthuc'] = $ketthuc;

        $account_model = $this->model->get('accountModel');
        $accounts = $account_model->getAllAccount();

        $additional_model = $this->model->get('additionalModel');
        $data_additional = array();

        $additionals = $additional_model->getAllAdditional(array('where'=>'additional_date <= '.strtotime($ngayketthuc)));
        
        foreach ($additionals as $additional) {
            $data_additional[$additional->debit]['no']['dauky'] = isset($data_additional[$additional->debit]['no']['dauky'])?$data_additional[$additional->debit]['no']['dauky']+$additional->money:$additional->money;
            $data_additional[$additional->credit]['co']['dauky'] = isset($data_additional[$additional->credit]['co']['dauky'])?$data_additional[$additional->credit]['co']['dauky']+$additional->money:$additional->money;
        }

        foreach ($accounts as $account) {
            if ($account->account_parent>0) {
                $nodauky[$account->account_id]=isset($data_additional[$account->account_id]['no']['dauky'])?$data_additional[$account->account_id]['no']['dauky']:0;
                $codauky[$account->account_id]=isset($data_additional[$account->account_id]['co']['dauky'])?$data_additional[$account->account_id]['co']['dauky']:0;
                
                $data_additional[$account->account_parent]['no']['dauky'] = isset($data_additional[$account->account_parent]['no']['dauky'])?$data_additional[$account->account_parent]['no']['dauky']+$nodauky[$account->account_id]:$nodauky[$account->account_id];
                $data_additional[$account->account_parent]['co']['dauky'] = isset($data_additional[$account->account_parent]['co']['dauky'])?$data_additional[$account->account_parent]['co']['dauky']+$codauky[$account->account_id]:$codauky[$account->account_id];
                
            }
        }

        $this->view->data['data_additional'] = $data_additional;
        $this->view->data['accounts'] = $accounts;

        $this->view->show('forward/index');
    }

    public function delete(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $additional_model = $this->model->get('additionalModel');
            $account_balance_model = $this->model->get('accountbalanceModel');
            $additional_date = strtotime(str_replace('/', '-', $_POST['additional_date']));
            $first = strtotime(date('01-m-Y',$additional_date));
            $last = strtotime(date('t-m-Y',strtotime('+1 day', $additional_date)));
            $additionals = $additional_model->getAllAdditional(array('where'=>'additional_date>='.$first.' AND additional_date<'.$last.' AND forward=1'));
            foreach ($additionals as $add) {
                $account_balance_model->queryAccount('DELETE FROM account_balance WHERE additional='.$add->additional_id);
                $additional_model->queryAdditional('DELETE FROM additional WHERE additional_id='.$add->additional_id);
            }
            

            echo "Đã xóa thành công!";
        }
    }

   public function complete(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $additional_model = $this->model->get('additionalModel');
            $account_balance_model = $this->model->get('accountbalanceModel');
            
            $document_date = strtotime(date('d-m-Y',strtotime(str_replace('/', '-', $_POST['document_date']))));
            $additional_date = strtotime(date('d-m-Y',strtotime(str_replace('/', '-', $_POST['additional_date']))));
            $document_number = trim($_POST['document_number']);
            
            $comment_5111_911 = trim($_POST['comment_5111_911']);
            $money_5111_911 = str_replace(',', '', $_POST['money_5111_911']);
            $comment_3331_911 = trim($_POST['comment_3331_911']);
            $money_3331_911 = str_replace(',', '', $_POST['money_3331_911']);
            $comment_515_911 = trim($_POST['comment_515_911']);
            $money_515_911 = str_replace(',', '', $_POST['money_515_911']);
            $comment_6321_911 = trim($_POST['comment_6321_911']);
            $money_6321_911 = str_replace(',', '', $_POST['money_6321_911']);
            $comment_635_911 = trim($_POST['comment_635_911']);
            $money_635_911 = str_replace(',', '', $_POST['money_635_911']);
            $comment_6411_911 = trim($_POST['comment_6411_911']);
            $money_6411_911 = str_replace(',', '', $_POST['money_6411_911']);
            $comment_6412_911 = trim($_POST['comment_6412_911']);
            $money_6412_911 = str_replace(',', '', $_POST['money_6412_911']);
            $comment_6413_911 = trim($_POST['comment_6413_911']);
            $money_6413_911 = str_replace(',', '', $_POST['money_6413_911']);
            $comment_6414_911 = trim($_POST['comment_6414_911']);
            $money_6414_911 = str_replace(',', '', $_POST['money_6414_911']);
            $comment_6418_911 = trim($_POST['comment_6418_911']);
            $money_6418_911 = str_replace(',', '', $_POST['money_6418_911']);
            $comment_6421_911 = trim($_POST['comment_6421_911']);
            $money_6421_911 = str_replace(',', '', $_POST['money_6421_911']);
            $comment_6422_911 = trim($_POST['comment_6422_911']);
            $money_6422_911 = str_replace(',', '', $_POST['money_6422_911']);
            $comment_6423_911 = trim($_POST['comment_6423_911']);
            $money_6423_911 = str_replace(',', '', $_POST['money_6423_911']);
            $comment_6425_911 = trim($_POST['comment_6425_911']);
            $money_6425_911 = str_replace(',', '', $_POST['money_6425_911']);
            $comment_6426_911 = trim($_POST['comment_6426_911']);
            $money_6426_911 = str_replace(',', '', $_POST['money_6426_911']);
            $comment_6427_911 = trim($_POST['comment_6427_911']);
            $money_6427_911 = str_replace(',', '', $_POST['money_6427_911']);
            $comment_6428_911 = trim($_POST['comment_6428_911']);
            $money_6428_911 = str_replace(',', '', $_POST['money_6428_911']);
            $comment_711_911 = trim($_POST['comment_711_911']);
            $money_711_911 = str_replace(',', '', $_POST['money_711_911']);
            $comment_811_911 = trim($_POST['comment_811_911']);
            $money_811_911 = str_replace(',', '', $_POST['money_811_911']);
            $comment_911_4111 = trim($_POST['comment_911_4111']);
            $money_911_4111 = str_replace(',', '', $_POST['money_911_4111']);
            $money_4111_911 = str_replace(',', '', $_POST['money_4111_911']);

            $first = strtotime(date('01-m-Y',$additional_date));
            $last = strtotime(date('t-m-Y',strtotime('+1 day', $additional_date)));

            $result = array(
                'result_5111_911'=>'Lỗi',
                'result_3331_911'=>'Lỗi',
                'result_515_911'=>'Lỗi',
                'result_6321_911'=>'Lỗi',
                'result_635_911'=>'Lỗi',
                'result_6411_911'=>'Lỗi',
                'result_6412_911'=>'Lỗi',
                'result_6413_911'=>'Lỗi',
                'result_6414_911'=>'Lỗi',
                'result_6418_911'=>'Lỗi',
                'result_6421_911'=>'Lỗi',
                'result_6422_911'=>'Lỗi',
                'result_6423_911'=>'Lỗi',
                'result_6425_911'=>'Lỗi',
                'result_6426_911'=>'Lỗi',
                'result_6427_911'=>'Lỗi',
                'result_6428_911'=>'Lỗi',
                'result_711_911'=>'Lỗi',
                'result_811_911'=>'Lỗi',
                'result_911_4111'=>'Lỗi',
            );

            
            $qr3 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_5111'].' AND credit='.$_POST['acc_911'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr3) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_5111_911,
                    'debit'=>$_POST['acc_5111'],
                    'credit'=>$_POST['acc_911'],
                    'money'=>$money_5111_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);

                $result['result_5111_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_5111_911,
                    'debit'=>$_POST['acc_5111'],
                    'credit'=>$_POST['acc_911'],
                    'money'=>$money_5111_911,
                    'forward'=>1,
                    );
                foreach ($qr3 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));

                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_5111_911'] = 'Cập nhật thành công';
            }
            $qr3 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_3331'].' AND credit='.$_POST['acc_911'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr3) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_3331_911,
                    'debit'=>$_POST['acc_3331'],
                    'credit'=>$_POST['acc_911'],
                    'money'=>$money_3331_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);

                $result['result_3331_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_3331_911,
                    'debit'=>$_POST['acc_3331'],
                    'credit'=>$_POST['acc_911'],
                    'money'=>$money_3331_911,
                    'forward'=>1,
                    );
                foreach ($qr3 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));

                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_3331_911'] = 'Cập nhật thành công';
            }
            $qr3 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_515'].' AND credit='.$_POST['acc_911'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr3) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_515_911,
                    'debit'=>$_POST['acc_515'],
                    'credit'=>$_POST['acc_911'],
                    'money'=>$money_515_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);

                $result['result_515_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_515_911,
                    'debit'=>$_POST['acc_515'],
                    'credit'=>$_POST['acc_911'],
                    'money'=>$money_515_911,
                    'forward'=>1,
                    );
                foreach ($qr3 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));

                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_515_911'] = 'Cập nhật thành công';
            }
            $qr5 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_911'].' AND credit='.$_POST['acc_6321'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr5) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6321_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6321'],
                    'money'=>$money_6321_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                $result['result_6321_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6321_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6321'],
                    'money'=>$money_6321_911,
                    'forward'=>1,
                    );
                foreach ($qr5 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));
                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_6321_911'] = 'Cập nhật thành công';
            }
            $qr5 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_911'].' AND credit='.$_POST['acc_635'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr5) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_635_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_635'],
                    'money'=>$money_635_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                $result['result_635_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_635_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_635'],
                    'money'=>$money_635_911,
                    'forward'=>1,
                    );
                foreach ($qr5 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));
                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_635_911'] = 'Cập nhật thành công';
            }
            $qr5 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_911'].' AND credit='.$_POST['acc_6411'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr5) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6411_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6411'],
                    'money'=>$money_6411_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                $result['result_6411_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6411_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6411'],
                    'money'=>$money_6411_911,
                    'forward'=>1,
                    );
                foreach ($qr5 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));
                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_6411_911'] = 'Cập nhật thành công';
            }
            $qr5 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_911'].' AND credit='.$_POST['acc_6412'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr5) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6412_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6412'],
                    'money'=>$money_6412_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                $result['result_6412_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6412_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6412'],
                    'money'=>$money_6412_911,
                    'forward'=>1,
                    );
                foreach ($qr5 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));
                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_6412_911'] = 'Cập nhật thành công';
            }
            $qr5 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_911'].' AND credit='.$_POST['acc_6413'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr5) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6413_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6413'],
                    'money'=>$money_6413_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                $result['result_6413_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6413_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6413'],
                    'money'=>$money_6413_911,
                    'forward'=>1,
                    );
                foreach ($qr5 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));
                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_6413_911'] = 'Cập nhật thành công';
            }
            $qr5 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_911'].' AND credit='.$_POST['acc_6414'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr5) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6414_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6414'],
                    'money'=>$money_6414_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                $result['result_6414_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6414_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6414'],
                    'money'=>$money_6414_911,
                    'forward'=>1,
                    );
                foreach ($qr5 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));
                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_6414_911'] = 'Cập nhật thành công';
            }
            
            $qr5 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_911'].' AND credit='.$_POST['acc_6418'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr5) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6418_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6418'],
                    'money'=>$money_6418_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                $result['result_6418_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6418_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6418'],
                    'money'=>$money_6418_911,
                    'forward'=>1,
                    );
                foreach ($qr5 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));
                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_6418_911'] = 'Cập nhật thành công';
            }
            $qr5 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_911'].' AND credit='.$_POST['acc_6421'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr5) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6421_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6421'],
                    'money'=>$money_6421_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                $result['result_6421_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6421_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6421'],
                    'money'=>$money_6421_911,
                    'forward'=>1,
                    );
                foreach ($qr5 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));
                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_6421_911'] = 'Cập nhật thành công';
            }
            $qr5 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_911'].' AND credit='.$_POST['acc_6422'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr5) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6422_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6422'],
                    'money'=>$money_6422_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                $result['result_6422_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6422_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6422'],
                    'money'=>$money_6422_911,
                    'forward'=>1,
                    );
                foreach ($qr5 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));
                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_6422_911'] = 'Cập nhật thành công';
            }
            $qr5 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_911'].' AND credit='.$_POST['acc_6423'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr5) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6423_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6423'],
                    'money'=>$money_6423_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                $result['result_6423_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6423_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6423'],
                    'money'=>$money_6423_911,
                    'forward'=>1,
                    );
                foreach ($qr5 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));
                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_6423_911'] = 'Cập nhật thành công';
            }
            $qr5 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_911'].' AND credit='.$_POST['acc_6425'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr5) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6425_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6425'],
                    'money'=>$money_6425_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                $result['result_6425_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6425_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6425'],
                    'money'=>$money_6425_911,
                    'forward'=>1,
                    );
                foreach ($qr5 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));
                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_6425_911'] = 'Cập nhật thành công';
            }
            $qr5 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_911'].' AND credit='.$_POST['acc_6426'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr5) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6426_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6426'],
                    'money'=>$money_6426_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                $result['result_6426_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6426_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6426'],
                    'money'=>$money_6426_911,
                    'forward'=>1,
                    );
                foreach ($qr5 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));
                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_6426_911'] = 'Cập nhật thành công';
            }
            $qr5 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_911'].' AND credit='.$_POST['acc_6427'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr5) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6427_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6427'],
                    'money'=>$money_6427_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                $result['result_6427_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6427_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6427'],
                    'money'=>$money_6427_911,
                    'forward'=>1,
                    );
                foreach ($qr5 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));
                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_6427_911'] = 'Cập nhật thành công';
            }
            $qr5 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_911'].' AND credit='.$_POST['acc_6428'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr5) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6428_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6428'],
                    'money'=>$money_6428_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                $result['result_6428_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_6428_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_6428'],
                    'money'=>$money_6428_911,
                    'forward'=>1,
                    );
                foreach ($qr5 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));
                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_6428_911'] = 'Cập nhật thành công';
            }
            $qr3 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_711'].' AND credit='.$_POST['acc_911'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr3) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_711_911,
                    'debit'=>$_POST['acc_711'],
                    'credit'=>$_POST['acc_911'],
                    'money'=>$money_711_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);

                $result['result_711_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_711_911,
                    'debit'=>$_POST['acc_711'],
                    'credit'=>$_POST['acc_911'],
                    'money'=>$money_711_911,
                    'forward'=>1,
                    );
                foreach ($qr3 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));

                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_711_911'] = 'Cập nhật thành công';
            }
            $qr5 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_911'].' AND credit='.$_POST['acc_811'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr5) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_811_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_811'],
                    'money'=>$money_811_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                $result['result_811_911'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_811_911,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_811'],
                    'money'=>$money_811_911,
                    'forward'=>1,
                    );
                foreach ($qr5 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));
                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_811_911'] = 'Cập nhật thành công';
            }
            $qr5 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_911'].' AND credit='.$_POST['acc_4111'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr5) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_911_4111,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_4111'],
                    'money'=>$money_4111_911,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);
                $result['result_911_4111'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_911_4111,
                    'debit'=>$_POST['acc_911'],
                    'credit'=>$_POST['acc_4111'],
                    'money'=>$money_4111_911,
                    'forward'=>1,
                    );
                foreach ($qr5 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));
                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_911_4111'] = 'Cập nhật thành công';
            }
            $qr3 = $additional_model->queryAdditional('SELECT * FROM additional WHERE debit='.$_POST['acc_4111'].' AND credit='.$_POST['acc_911'].' AND additional_date>='.$first.' AND additional_date<'.$last.' LIMIT 1');
            if (!$qr3) {
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_911_4111,
                    'debit'=>$_POST['acc_4111'],
                    'credit'=>$_POST['acc_911'],
                    'money'=>$money_911_4111,
                    'forward'=>1,
                    );
                $additional_model->createAdditional($data);
                $id_additional = $additional_model->getLastAdditional()->additional_id;
                $data_debit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['debit'],
                    'money' => $data['money'],
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $data_credit = array(
                    'account_balance_date' => $data['additional_date'],
                    'account' => $data['credit'],
                    'money' => (0-$data['money']),
                    'week' => (int)date('W', $data['additional_date']),
                    'year' => (int)date('Y', $data['additional_date']),
                    'additional' => $id_additional,
                    'account_balance_type'=>1,
                );
                $account_balance_model->createAccount($data_debit);
                $account_balance_model->createAccount($data_credit);

                $result['result_911_4111'] = 'Thêm thành công';
            }
            else{
                $data = array(
                    'document_date'=>$document_date,
                    'document_number'=>$document_number,
                    'additional_date'=>$additional_date,
                    'additional_comment'=>$comment_911_4111,
                    'debit'=>$_POST['acc_4111'],
                    'credit'=>$_POST['acc_911'],
                    'money'=>$money_911_4111,
                    'forward'=>1,
                    );
                foreach ($qr3 as $q) {
                    $data_debit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['debit'],
                        'money' => $data['money'],
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $data_credit = array(
                        'account_balance_date' => $data['additional_date'],
                        'account' => $data['credit'],
                        'money' => (0-$data['money']),
                        'week' => (int)date('W', $data['additional_date']),
                        'year' => (int)date('Y', $data['additional_date']),
                        'additional' => $q->additional_id,
                        'account_balance_type'=>1,
                    );
                    $account_balance_model->updateAccount($data_debit,array('account' => $q->debit,'additional' => $q->additional_id));
                    $account_balance_model->updateAccount($data_credit,array('account' => $q->credit,'additional' => $q->additional_id));

                    $additional_model->updateAdditional($data,array('additional_id'=>$q->additional_id));
                }
                $result['result_911_4111'] = 'Cập nhật thành công';
            }
            

            echo json_encode($result);

        }
    }
    

}
?>