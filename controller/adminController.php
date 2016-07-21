<?php
Class adminController Extends baseController {
    public function index() {
    	$this->view->setLayout('admin');

        /*** set a template variable ***/
            //$this->view->data['welcome'] = 'Welcome to CAI MEP TRADING !';
        /*** load the index template ***/
            $this->view->data['title'] = 'Dịch vụ vận tải, xuất nhập khẩu, thủ tục hải quan, chỉnh sửa manifest';
            $this->view->show('admin/index');
    }
    public function queryscript(){
        $costs_model = $this->model->get('costsModel');
        $costs_model->queryCosts('ALTER TABLE `costs` ADD `additional` INT NULL');
    }
    public function autoscript(){
    	$costs_model = $this->model->get('costsModel');
    	$payable_model = $this->model->get('payableModel');

    	$today = strtotime(date('d-m-Y H:i:s'));

        $data = array(
            'approve' => 10,
        );

        $data_costs1 = array(
            'where' => '(pending IS NULL OR pending=0) AND (approve IS NULL OR approve <= 0) AND (check_equipment > 0 OR check_energy > 0 )',
        );
        $costs1 = $costs_model->getAllCosts($data_costs1);
        foreach ($costs1 as $cost) {
            $costs_model->updateCosts($data,array('costs_id'=>$cost->costs_id));
        }
        

    	$data_costs = array(
    		'where' => '(pending IS NULL OR pending=0) AND (approve IS NULL OR approve <= 0) AND ( money <= 5000000 OR money_in > 0 )',
    	);

    	$data_payable = array(
    		'where' => '(pending IS NULL OR pending=0) AND approve2 > 0 AND (approve IS NULL OR approve <= 0)',
    	);

    	

    	$costs = $costs_model->getAllCosts($data_costs);
    	$payables = $payable_model->getAllCosts($data_payable);

    	foreach ($costs as $cost) {
    		$hourdiff = round(($today - $cost->costs_create_date)/3600, 1);

    		if ( ($cost->money <= 500000 || $cost->money_in > 0 ) && $hourdiff >= 12) {
    			$costs_model->updateCosts($data,array('costs_id'=>$cost->costs_id));
    		}
    		elseif ($cost->money > 500000 && $cost->money <= 1000000 && $hourdiff >= 24) {
    			$costs_model->updateCosts($data,array('costs_id'=>$cost->costs_id));
    		}
    		elseif ($cost->money > 1000000 && $cost->money <= 1500000 && $hourdiff >= 36) {
    			$costs_model->updateCosts($data,array('costs_id'=>$cost->costs_id));
    		}
    		elseif ($cost->money > 1500000 && $cost->money <= 2000000 && $hourdiff >= 60) {
    			$costs_model->updateCosts($data,array('costs_id'=>$cost->costs_id));
    		}
    		elseif ($cost->money > 2000000 && $cost->money <= 5000000 && $hourdiff >= 120) {
    			$costs_model->updateCosts($data,array('costs_id'=>$cost->costs_id));
    		}
    	}

    	foreach ($payables as $payable) {
    		$hourdiff = round(($today - $payable->payable_create_date)/3600, 1);

    		if ($payable->money <= 500000 && $hourdiff >= 12) {
    			$payable_model->updateCosts($data,array('payable_id'=>$payable->payable_id));
    		}
    		elseif ($payable->money > 500000 && $payable->money <= 1000000 && $hourdiff >= 24) {
    			$payable_model->updateCosts($data,array('payable_id'=>$payable->payable_id));
    		}
    		elseif ($payable->money > 1000000 && $payable->money <= 1500000 && $hourdiff >= 36) {
    			$payable_model->updateCosts($data,array('payable_id'=>$payable->payable_id));
    		}
    		elseif ($payable->money > 1500000 && $payable->money <= 2000000 && $hourdiff >= 60) {
    			$payable_model->updateCosts($data,array('payable_id'=>$payable->payable_id));
    		}
    		elseif ($payable->money > 2000000 && $payable->money <= 5000000 && $hourdiff >= 120) {
    			$payable_model->updateCosts($data,array('payable_id'=>$payable->payable_id));
    		}
    	}

        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
        $filename = "cron_logs.txt";
        $text = date('d/m/Y H:i:s')."|cron|"."edit"."\n"."\r\n";
        
        $fh = fopen($filename, "a") or die("Could not open log file.");
        fwrite($fh, $text) or die("Could not write file!");
        fclose($fh);

    }

    public function checklockuser(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($_POST['data'] == 0) {
                echo 0;
            }
            else{
                $user_model = $this->model->get('userModel');
            
                $user = $user_model->getUserByWhere(array('user_id' => $_POST['data']));
                echo $user->user_lock;
            }
            
        }
    }

    public function notification(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $costs_model = $this->model->get('costsModel');
            $payable_model = $this->model->get('payableModel');
            $total = "";
            
            if (isset($_SESSION['role_logined'])) {
                if($_SESSION['role_logined'] == 1){
                    $data_costs = array(
                        'where' => '(approve IS NULL OR approve <= 0) AND (pay_money IS NULL OR pay_money != money)',
                    );

                    $data_payable = array(
                        'where' => 'approve3 > 0 AND (approve IS NULL OR approve <= 0) AND (pay_money IS NULL OR pay_money != money)',
                    );

                    $costs = $costs_model->getAllCosts($data_costs);
                    $payables = $payable_model->getAllCosts($data_payable);

                    $total = 0;
                    foreach ($costs as $cost) {
                        $total++;
                    }
                    foreach ($payables as $payable) {
                        $total++;
                    }
                }
                else if($_SESSION['role_logined'] == 3){
                    $data_costs = array();

                    $data_payable = array(
                        'where' => '(approve3 IS NULL OR approve3 <= 0) AND (pay_money IS NULL OR pay_money != money)',
                    );

                    $payables = $payable_model->getAllCosts($data_payable);

                    $total = 0;
                    foreach ($payables as $payable) {
                        $total++;
                    }
                }
                else if($_SESSION['role_logined'] == 8){
                    $data_costs = array(
                        'where' => '(approve2 IS NULL OR approve2 <= 0) AND (pay_money IS NULL OR pay_money != money)',
                    );

                    $data_payable = array(
                        'where' => '(approve2 IS NULL OR approve2 <= 0) AND (pay_money IS NULL OR pay_money != money)',
                    );

                    $costs = $costs_model->getAllCosts($data_costs);
                    $payables = $payable_model->getAllCosts($data_payable);

                    $total = 0;
                    foreach ($costs as $cost) {
                        $total++;
                    }
                    foreach ($payables as $payable) {
                        $total++;
                    }
                }
                else{
                    $data_costs = array(
                        'where' => '(approve IS NULL OR approve <= 0) AND (pay_money IS NULL OR pay_money != money)',
                    );

                    $data_payable = array(
                        'where' => '(approve IS NULL OR approve <= 0) AND (pay_money IS NULL OR pay_money != money)',
                    );

                    $costs = $costs_model->getAllCosts($data_costs);
                    $payables = $payable_model->getAllCosts($data_payable);

                    $total = 0;
                    foreach ($costs as $cost) {
                        $total++;
                    }
                    foreach ($payables as $payable) {
                        $total++;
                    }
                }
            }
            

            

            echo $total;

        }
    }


}
?>