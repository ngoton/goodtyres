<?php
Class secsController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Rút sec';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'sec_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            
            
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m/Y');
            $ngaytaobatdau = date('m/Y');
        }
        $limit = 18446744073709;
        $ngaytao = date('m/Y',strtotime($batdau));
        $ngaytaobatdau = date('m/Y',strtotime($ketthuc));

        $sec_cost_model = $this->model->get('seccostModel');

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;
        $bank_data = array();
        foreach ($banks as $bank) {
            $bank_data['name'][$bank->bank_id] = $bank->bank_name;
            $bank_data['id'][$bank->bank_id] = $bank->bank_id;
        }
        $this->view->data['bank_data'] = $bank_data;

        $sec_model = $this->model->get('secModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'sec_date >= '.strtotime($batdau).' AND sec_date <= '.strtotime($ketthuc),
        );
        
        $tongsodong = count($sec_model->getAllCosts($data));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'sec_date >= '.strtotime($batdau).' AND sec_date <= '.strtotime($ketthuc),
            );
      
        

        $secs = $sec_model->getAllCosts($data);

        $sec_data = array();

        foreach ($secs as $sec) {
            $sec_costs = $sec_cost_model->getAllCosts(array('where'=>'sec='.$sec->sec_id));
            foreach ($sec_costs as $sec_cost) {
                $sec_data[$sec->sec_id][] = $sec_cost;
            }
        }

        $this->view->data['sec_datas'] = $sec_data;

        $this->view->data['secs'] = $secs;
        $this->view->data['lastID'] = isset($sec_model->getLastCosts()->sec_id)?$sec_model->getLastCosts()->sec_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('secs/index');
    }

    public function lists() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 7 && $_SESSION['role_logined']!=8 && $_SESSION['role_logined']!=9) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Rút sec';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'secs_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            
            
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $ngaytao = date('m/Y');
            $ngaytaobatdau = date('m/Y');
        }
        $limit = 18446744073709;
        $ngaytao = date('m/Y',strtotime($batdau));
        $ngaytaobatdau = date('m/Y',strtotime($ketthuc));

        $costs_model = $this->model->get('costsModel');
        $pay_model = $this->model->get('payModel');

        $bank_model = $this->model->get('bankModel');
        $banks = $bank_model->getAllBank();
        $this->view->data['banks'] = $banks;
        $bank_data = array();
        foreach ($banks as $bank) {
            $bank_data['name'][$bank->bank_id] = $bank->bank_name;
            $bank_data['id'][$bank->bank_id] = $bank->bank_id;
        }
        $this->view->data['bank_data'] = $bank_data;

        $sec_model = $this->model->get('secsModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $join = array('table'=>'costs','where'=>'secs.secs_id=costs.sec AND costs.check_sec=1');
        
        $data = array(
            'where' => 'secs_date >= '.strtotime($batdau).' AND secs_date <= '.strtotime($ketthuc),
        );
        
        $tongsodong = count($sec_model->getAllCosts($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'secs_date >= '.strtotime($batdau).' AND secs_date <= '.strtotime($ketthuc),
            );
      
        $join_pay = array('table'=>'payable','where'=>'pay.payable=payable.payable_id');

        $secs = $sec_model->getAllCosts($data,$join);

        $sec_data = array();

        foreach ($secs as $sec) {
            $sec_costs = $costs_model->getAllCosts(array('where'=>'(check_sec IS NULL OR check_sec <= 0 ) AND sec='.$sec->secs_id));
            foreach ($sec_costs as $sec_cost) {
                $sec_cost->sec_cost_id = $sec_cost->costs_id;
                if ($sec_cost->sale_estimate==1) {
                    $sec_cost->sec_cost_type = 2;
                }
                else if ($sec_cost->agent_estimate==1) {
                    $sec_cost->sec_cost_type = 4;
                }
                else if ($sec_cost->trading_estimate==1) {
                    $sec_cost->sec_cost_type = 3;
                }
                else if ($sec_cost->tcmt_estimate==1) {
                    $sec_cost->sec_cost_type = 5;
                }
                else{
                    $sec_cost->sec_cost_type = 1;
                }

                $sec_data[$sec->secs_id][] = $sec_cost;
            }

            //$sec_pays = $pay_model->getAllCosts(array('where'=>'pay.sec='.$sec->secs_id),$join_pay);
            $sec_pays = $pay_model->queryCosts('SELECT pay_id, pay.pay_date, pay.money, pay_comment, pay.source, pay.sec, code, sale_report, agent, agent_manifest, trading, invoice FROM pay, payable WHERE pay.payable=payable.payable_id AND pay.sec='.$sec->secs_id);
            foreach ($sec_pays as $sec_pay) {
                $sec_cost->sec_cost_id = $sec_pay->pay_id;
                if ($sec_pay->agent>0 || $sec_pay->agent_manifest>0) {
                    $sec_pay->sec_cost_type = 4;
                }
                else if ($sec_pay->sale_report>0) {
                    $sec_pay->sec_cost_type = 2;
                }
                else if ($sec_pay->trading>0) {
                    $sec_pay->sec_cost_type = 3;
                }
                else if ($sec_pay->invoice>0) {
                    $sec_pay->sec_cost_type = 5;
                }

                $sec_pay->pay_money = $sec_pay->money;
                $sec_pay->comment = $sec_pay->pay_comment;

                $sec_data[$sec->secs_id][] = $sec_pay;
            }

        }

        $this->view->data['sec_datas'] = $sec_data;

        $this->view->data['secs'] = $secs;
        $this->view->data['lastID'] = isset($sec_model->getLastCosts()->secs_id)?$sec_model->getLastCosts()->secs_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('secs/lists');
    }

    public function import(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $secs = $this->model->get('secsModel');
            $bank = $this->model->get('bankModel');

            $objPHPExcel = new PHPExcel();
            // Set properties
            if (pathinfo($_FILES['import']['name'], PATHINFO_EXTENSION) == "xls") {
                $objReader = PHPExcel_IOFactory::createReader('Excel5');
            }
            else if (pathinfo($_FILES['import']['name'], PATHINFO_EXTENSION) == "xlsx") {
                $objReader = PHPExcel_IOFactory::createReader('Excel2007');
            }
            
            $objReader->setReadDataOnly(false);

            $objPHPExcel = $objReader->load($_FILES['import']['tmp_name']);
            $objWorksheet = $objPHPExcel->getActiveSheet();

            

            $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
            $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'

            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

            //var_dump($objWorksheet->getMergeCells());die();

                for ($row = 8; $row <= $highestRow; ++ $row) {
                    $val = array();
                    for ($col = 0; $col < $highestColumnIndex; ++ $col) {
                        $cell = $objWorksheet->getCellByColumnAndRow($col, $row);
                        // Check if cell is merged
                        foreach ($objWorksheet->getMergeCells() as $cells) {
                            if ($cell->isInRange($cells)) {
                                $currMergedCellsArray = PHPExcel_Cell::splitRange($cells);
                                $cell = $objWorksheet->getCell($currMergedCellsArray[0][0]);
                                break;
                                
                            }
                        }
                        //$val[] = $cell->getValue();
                        $val[] = is_numeric($cell->getCalculatedValue()) ? round($cell->getCalculatedValue()) : $cell->getCalculatedValue();
                        //here's my prob..
                        //echo $val;
                    }
                    if ($val[1] != null && $val[2] != null && $val[3] != null) {
                        $secs_date = PHPExcel_Shared_Date::ExcelToPHP(trim($val[0]));                                      
                        $secs_date = $secs_date-3600;

                        $secs_bank = $bank->getBankByWhere(array('bank_name'=>trim($val[1])))->bank_id;
                            
                            if(!$secs->getCostsByWhere(array('secs_date'=>$secs_date,'secs_bank'=>$secs_bank,'secs_name'=>trim($val[3])))) {
                                $data = array(
                                'secs_date' => $secs_date,
                                'secs_bank' => $secs_bank,
                                'secs_name' => trim($val[3]),
                                'secs_money' => trim($val[2]),
                                );
                                $secs->createCosts($data);
                            }

                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('secs');
        }
        $this->view->show('secs/import');

    }


    

}
?>