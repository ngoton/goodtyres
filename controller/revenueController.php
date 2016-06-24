<?php
Class revenueController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 ) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý doanh số bán hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;
            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'sales_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $ngaytao = date('m/Y');
            $ngaytaobatdau = date('m/Y');
        }

        $thangbatdau = explode("/", $ngaytaobatdau);
        $thangketthuc = explode("/", $ngaytao);

        $daysale = '( sales_create_time LIKE "%'.$thangbatdau[0].'/'.$thangbatdau[1].'%" ';
        $dayaccounting = '( accounting_create_time LIKE "%'.$thangbatdau[0].'/'.$thangbatdau[1].'%" ';
        $daysalary = '( salary_create_time LIKE "%'.$thangbatdau[0].'/'.$thangbatdau[1].'%" ';
        if($thangbatdau[1] == $thangketthuc[1]){
            for ($i=0; $i < ($thangketthuc[0]-$thangbatdau[0]); $i++) { 
                $daysale .= 'OR sales_create_time LIKE "%'.($thangbatdau[0]+$i+1).'/'.$thangbatdau[1].'%" ';
                $dayaccounting .= 'OR accounting_create_time LIKE "%'.($thangbatdau[0]+$i+1).'/'.$thangbatdau[1].'%" ';
                $daysalary .= 'OR salary_create_time LIKE "%'.($thangbatdau[0]+$i+1).'/'.$thangbatdau[1].'%" ';
            }
        }
        elseif ($thangketthuc[1] > $thangbatdau[1]) {
            for ($i=0; $i < (12-$thangbatdau[0]); $i++) { 
                $daysale .= 'OR sales_create_time LIKE "%'.($thangbatdau[0]+$i+1).'/'.$thangbatdau[1].'%" ';
                $dayaccounting .= 'OR accounting_create_time LIKE "%'.($thangbatdau[0]+$i+1).'/'.$thangbatdau[1].'%" ';
                $daysalary .= 'OR salary_create_time LIKE "%'.($thangbatdau[0]+$i+1).'/'.$thangbatdau[1].'%" ';
            }
            for ($j=0; $j < $thangketthuc[0]; $j++) { 
                for ($z=0; $z < ($thangketthuc[1] - $thangbatdau[1]); $z++) { 
                  
                    $daysale .= 'OR sales_create_time LIKE "%'.($thangketthuc[0]-$j).'/'.($thangketthuc[1]-$z).'%" ';
                    $dayaccounting .= 'OR accounting_create_time LIKE "%'.($thangketthuc[0]-$j).'/'.($thangketthuc[1]-$z).'%" ';
                    $daysalary .= 'OR salary_create_time LIKE "%'.($thangketthuc[0]-$j).'/'.($thangketthuc[1]-$z).'%" ';
                }
            }
            if ( ($thangketthuc[1]-$thangbatdau[1]) > 1) {
                for ($y=0; $y < (12-$thangketthuc[0]); $y++) { 
                    $daysale .= 'OR sales_create_time LIKE "%'.($thangketthuc[0]+$y+1).'/'.$thangketthuc[1].'%" ';
                    $dayaccounting .= 'OR accounting_create_time LIKE "%'.($thangketthuc[0]+$y+1).'/'.$thangketthuc[1].'%" ';
                    $daysalary .= 'OR salary_create_time LIKE "%'.($thangketthuc[0]+$y+1).'/'.$thangketthuc[1].'%" ';
                }
            }
        }
        $daysale .= ' )';
        $dayaccounting .= ' )';
        $daysalary .= ' )';
        //var_dump($daysalary);die();

        $staff_model = $this->model->get('staffModel');
        $sales_model = $this->model->get('salesModel');
        $sonews = 1000;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = null;

        
        $join = array('table'=>'customer, accounting','where'=>'sales.customer = customer.customer_id AND sales.code = accounting.accounting_code AND sales.comment = accounting.accounting_comment AND ( '.$daysale.' AND '.$dayaccounting.' )');

        
        $tongsodong = count($sales_model->getAllSales($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['ngaytao'] = $ngaytao;
        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;
        $this->view->data['sonews'] = $sonews;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            );
        
        if ($keyword != '') {
            $search = '( customer_name LIKE "%'.$keyword.'%" 
                OR code LIKE "%'.$keyword.'%" 
                OR comment LIKE "%'.$keyword.'%" 
                OR revenue LIKE "%'.$keyword.'%" 
                OR cost LIKE "%'.$keyword.'%" 
                OR profit LIKE "%'.$keyword.'%" 
                OR m in (SELECT staff_id FROM staff WHERE staff_name LIKE "%'.$keyword.'%")
                OR s in (SELECT staff_id FROM staff WHERE staff_name LIKE "%'.$keyword.'%")
                OR c in (SELECT staff_id FROM staff WHERE staff_name LIKE "%'.$keyword.'%"))';
            if ($ngaytao != '') {
                $create_time = ' ( '.$daysale.' AND '.$dayaccounting.' )';
                $data['where'] = $search.$create_time;
            }
            else
                $data['where'] = $search;
        }
        if ($ngaytao != '' && $keyword == '') {
            $create_time = ' ( '.$daysale.' AND '.$dayaccounting.' )';
            $data['where'] = $create_time;
        }
        
        $staff = $staff_model->getAllStaff(array('where'=>$daysalary),array('table'=>'salary, sales','where'=>'staff.staff_id = salary.staff AND (staff_id = m OR staff_id = s OR staff_id = c) AND sales_create_time = salary_create_time GROUP BY staff_id'));
        $this->view->data['staff_all'] = $staff;

        $staff = $staff_model->getAllStaff(array('where'=>$daysalary),array('table'=>'salary, sales','where'=>'staff.staff_id = salary.staff AND (staff_id = m OR staff_id = s OR staff_id = c) AND sales_create_time = salary_create_time'));
        //echo json_encode($staff);die();
        
        
        $staff_data = array();
        foreach ($staff as $staff) {

            $staff_data['staff_id'][$staff->salary_create_time][$staff->staff_id] = $staff->staff_id;
            $staff_data['staff_name'][$staff->salary_create_time][$staff->staff_id] = $staff->staff_name;
            $staff_data['basic_salary'][$staff->salary_create_time][$staff->staff_id] = $staff->basic_salary;
            //echo json_encode($staff->basic_salary);die();
        }
        
        $this->view->data['staff'] = $staff_data;

        //echo json_encode($staff_data);die();

        $this->view->data['sales'] = $sales_model->getAllSales($data,$join);
        $this->view->data['lastID'] = $sales_model->getLastSales()->sales_id;
        

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('revenue/index');
    }

    function export(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        //var_dump($this->registry->router->addition);die();

        if ($this->registry->router->param_id != null && $this->registry->router->page != null && $this->registry->router->order_by != null && $this->registry->router->order != null) {
            //$ngaytao = $this->registry->router->param_id.'/'.$this->registry->router->page;
            if ($this->registry->router->addition != null) {
                $staff_id = $this->registry->router->addition;
                $ngaybatdau = $this->registry->router->param_id;
                $ngayketthuc = $this->registry->router->order_by;
                $nam = $this->registry->router->page;
                $namketthuc = $this->registry->router->order;

                require("lib/Classes/PHPExcel/IOFactory.php");
                require("lib/Classes/PHPExcel.php");

                $objPHPExcel = new PHPExcel();

                $staff_model = $this->model->get('staffModel');
                $sales_model = $this->model->get('salesModel');

                if ($namketthuc==$nam) {
                    $vonglap = $ngayketthuc-$ngaybatdau;
                }
                elseif ($namketthuc-$nam == 1) {
                    $vonglap = (12-$ngaybatdau)+$ngayketthuc;
                }
                elseif ($namketthuc-$nam > 1) {
                    $vonglap = (12-$ngaybatdau)+$ngayketthuc+($namketthuc-$nam-1)*12;
                }

                $m = 0;
                $n = 0;
                for ($z=0; $z <= $vonglap; $z++) { 
                    if (($ngaybatdau+$z) >12 ) {
                        $m = 12;
                        $n = 1;
                    }
                    $join = array('table'=>'customer, accounting','where'=>'sales.customer = customer.customer_id AND sales.code = accounting.accounting_code AND sales.comment = accounting.accounting_comment');
                    $data = array(
                        'where' => ' ( (m = '.$staff_id.' OR s = '.$staff_id.' OR c = '.$staff_id.') AND sales_create_time LIKE "%'. ($ngaybatdau+$z-$m).'/'.($nam+$n).'%" AND accounting_create_time LIKE "%'. ($ngaybatdau+$z-$m).'/'.($nam+$n).'%" )',
                        );
                    $sale = $sales_model->getAllSales($data,$join);

                    $staff_sale = $staff_model->getAllStaff(array('where'=>'salary_create_time LIKE "%'.($ngaybatdau+$z-$m).'/'.($nam+$n).'%"'),array('table'=>'salary, sales','where'=>'staff.staff_id = salary.staff AND (m = '.$staff_id.' OR s = '.$staff_id.' OR c = '.$staff_id.') AND sales_create_time = salary_create_time AND staff_id = '.$staff_id));
            

                    //$staff = $staff_model->getAllStaff(array('where'=>'salary_create_time LIKE "%'.($ngaybatdau+$z-$m).'/'.($nam+$n).'%"'),array('table'=>'salary,sales','where'=>'staff.staff_id = salary.staff AND (m = '.$staff_id.' OR s = '.$staff_id.' OR c = '.$staff_id.') AND sales_create_time = salary_create_time'));
                    $staff_data = array();
                    foreach ($staff_sale as $staff) {
                        $staff_data['staff_id'][$staff->salary_create_time][$staff->staff_id] = $staff->staff_id;
                        $staff_data['staff_name'][$staff->salary_create_time][$staff->staff_id] = $staff->staff_name;
                        $staff_data['basic_salary'][$staff->salary_create_time][$staff->staff_id] = $staff->basic_salary;
                    }

                    $objPHPExcel->createSheet();
                    $index_worksheet = $z; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
                    $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A1', 'BẢNG TÍNH LƯƠNG DOANH SỐ '.strtoupper($staff->staff_name).' '.($ngaybatdau+$z-$m).'/'.($nam+$n))
                       ->setCellValue('A3', 'STT')
                       ->setCellValue('B3', 'Code')
                       ->setCellValue('C3', 'MSC')
                       ->setCellValue('C4', 'M')
                       ->setCellValue('D4', 'S')
                       ->setCellValue('E4', 'C');


                    $objPHPExcel->getActiveSheet()->mergeCells('A1:E1');
                    $objPHPExcel->getActiveSheet()->mergeCells('A3:A4');
                    $objPHPExcel->getActiveSheet()->mergeCells('B3:B4');
                    $objPHPExcel->getActiveSheet()->mergeCells('C3:E3');
                    $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('A3')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('B3')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('C3')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('C4')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('D4')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('E4')->getFont()->setBold(true);
                    
                    
                    
                    $total_bonus = array();
                    $arr_msc = array();

                    if ($sale) {

                        foreach ($sale as $row) {
                             /*******/
                            $arr_msc[$row->sales_create_time][$row->m]['m'][$row->sales_id][] = $row->profit;
                            $arr_msc[$row->sales_create_time][$row->s]['s'][$row->sales_id][] = $row->profit;
                            $arr_msc[$row->sales_create_time][$row->c]['c'][$row->sales_id][] = $row->profit;
                            /********/
                            $staff_msc[$row->sales_create_time][$row->m]['m'][$row->sales_id] = $row->profit;
                            $staff_msc[$row->sales_create_time][$row->s]['s'][$row->sales_id] = $row->profit;
                            $staff_msc[$row->sales_create_time][$row->c]['c'][$row->sales_id] = $row->profit;
                        }
                        $array_sum = 0;
                        $total_bonus = array();

                            $m_sum = array();
                            $s_sum = array();
                            $c_sum = array();
                            $salary_arr = array();

                            $thuong_m = 0;
                            $thuong_s = 0;
                            $thuong_c = 0;

                            $arr_thuong_m = array();
                            $arr_thuong_s = array();
                            $arr_thuong_c = array();

                        foreach ($arr_msc as $thang => $mang) {
                            
                            
                            foreach ($mang as $key => $value) {
                              //var_dump($value['m']);die();
                                $m_sum[$thang][$key] = 0;
                                $s_sum[$thang][$key] = 0;
                                $c_sum[$thang][$key] = 0;
                                $total_bonus[$thang][$key] = 0;

                                $arr_thuong_m[$key][$thang] = 0;
                                $arr_thuong_s[$key][$thang] = 0;
                                $arr_thuong_c[$key][$thang] = 0;

                              if (isset($value['m'])) {
                                foreach ($value['m'] as $key1 => $value1) {
                                    $m_sum[$thang][$key] += array_sum($value1);
                                    
                                }
                              }
                              if (isset($value['s'])) {
                                foreach ($value['s'] as $key2 => $value2) {
                                    $s_sum[$thang][$key] += array_sum($value2);
                                    
                                }
                              }
                              if (isset($value['c'])) {
                                foreach ($value['c'] as $key3 => $value3) {
                                    $c_sum[$thang][$key] += array_sum($value3);
                                    
                                }
                              }
                              
                              //$total_bonus[$key] = (isset($m_bonus[$key])?(array_sum($m_bonus[$key])>0?array_sum($m_bonus[$key]):0):0)+(isset($s_bonus[$key])?(array_sum($s_bonus[$key])>0?array_sum($s_bonus[$key]):0):0)+(isset($c_bonus[$key])?(array_sum($c_bonus[$key])>0?array_sum($c_bonus[$key]):0):0);
                              //$array_sum[] = $total_bonus[$key];

                              $thuong_m = isset($staff_data['basic_salary'][$thang][$key]) ? (($m_sum[$thang][$key]-(3*$staff_data['basic_salary'][$thang][$key]))*10/100) : 0;
                              $thuong_s = isset($staff_data['basic_salary'][$thang][$key]) ? (($s_sum[$thang][$key]-(3*$staff_data['basic_salary'][$thang][$key]))*10/100) : 0;
                              $thuong_c = isset($staff_data['basic_salary'][$thang][$key]) ? (($c_sum[$thang][$key]-(3*$staff_data['basic_salary'][$thang][$key]))*10/100) : 0;


                              $arr_thuong_m[$key][$thang] += ($thuong_m > 0 ? $thuong_m : 0);
                              $arr_thuong_s[$key][$thang] += ($thuong_s > 0 ? $thuong_s : 0);
                              $arr_thuong_c[$key][$thang] += ($thuong_c > 0 ? $thuong_c : 0);


                              $total_bonus[$thang][$key] += ($thuong_m > 0 ? $thuong_m : 0) + ($thuong_s > 0 ? $thuong_s : 0) + ($thuong_c > 0 ? $thuong_c : 0);
                              
                            }
                            //$array_sum[] = array_sum($total_bonus);
                            //var_dump($total_bonus);
                            $array_sum += array_sum($total_bonus[$thang]);
                        }

                    }

                        $hang = 5;
                        $i = 1;
                        foreach ($staff_sale as $staff_sale) {

                        $objPHPExcel->setActiveSheetIndex($index_worksheet)
                           ->setCellValue('A'.$hang, $i++)
                           ->setCellValue('B'.$hang, $staff_sale->code)
                           ->setCellValue('C'.$hang, isset($staff_msc[$staff_sale->sales_create_time][$staff_sale->staff_id]['m'][$staff_sale->sales_id])?$staff_msc[$staff_sale->sales_create_time][$staff_sale->staff_id]['m'][$staff_sale->sales_id]:null)
                           ->setCellValue('D'.$hang, isset($staff_msc[$staff_sale->sales_create_time][$staff_sale->staff_id]['s'][$staff_sale->sales_id])?$staff_msc[$staff_sale->sales_create_time][$staff_sale->staff_id]['s'][$staff_sale->sales_id]:null)
                           ->setCellValue('E'.$hang, isset($staff_msc[$staff_sale->sales_create_time][$staff_sale->staff_id]['c'][$staff_sale->sales_id])?$staff_msc[$staff_sale->sales_create_time][$staff_sale->staff_id]['c'][$staff_sale->sales_id]:null);

                           $hang++;
                        }

                        $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();
                        $objPHPExcel->setActiveSheetIndex($index_worksheet)
                           ->setCellValue('C'.($highestRow+1), array_sum($arr_thuong_m[$staff_id]))
                           ->setCellValue('D'.($highestRow+1), array_sum($arr_thuong_s[$staff_id]))
                           ->setCellValue('E'.($highestRow+1), array_sum($arr_thuong_c[$staff_id]))
                           ->setCellValue('A'.($highestRow+2), 'THƯỞNG THÁNG')
                           ->setCellValue('C'.($highestRow+2), '=SUM(C'.($highestRow+1).':E'.($highestRow+1).')');


                        $objPHPExcel->getActiveSheet()->mergeCells('A'.($highestRow+2).':B'.($highestRow+2));
                        $objPHPExcel->getActiveSheet()->mergeCells('C'.($highestRow+2).':E'.($highestRow+2));

                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+1).':E'.($highestRow+2))->getFont()->setBold(true);
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+1).':E'.($highestRow+2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+1).':E'.($highestRow+2))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $objPHPExcel->getActiveSheet()->getStyle('A1:E4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                        $objPHPExcel->getActiveSheet()->getStyle('A1:E4')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        

                        $objPHPExcel->getActiveSheet()->getStyle('C5:E'.($highestRow+2))->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");
                        $objPHPExcel->getActiveSheet()->getStyle("A1:E".($highestRow+2))->getFont()->setName('Times New Roman');
                        
                        $objPHPExcel->getActiveSheet()->getStyle('A3:E4')->getAlignment()->setWrapText(true);
                        $objPHPExcel->getActiveSheet()->getStyle('A3:E4')->applyFromArray(
                            array(
                                'borders' => array(
                                    'allborders' => array(
                                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                                        'color' => array('argb' => '000000'),
                                    ),
                                ),
                                'fill' => array(
                                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                    'color' => array('rgb' => '00E0FF')
                                )
                            )
                        );
                        $objPHPExcel->getActiveSheet()->getStyle('A3:E'.($highestRow+2))->applyFromArray(
                            array(
                                'borders' => array(
                                    'outline' => array(
                                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                                        'color' => array('argb' => '000000'),
                                    ),
                                ),
                            )
                        );
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+2).':E'.($highestRow+2))->applyFromArray(
                            array(
                                'borders' => array(
                                    'outline' => array(
                                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                                        'color' => array('argb' => '000000'),
                                    ),
                                ),
                            )
                        );
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+1).':E'.($highestRow+1))->applyFromArray(
                            array(
                                'borders' => array(
                                    'outline' => array(
                                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                                        'color' => array('argb' => '000000'),
                                    ),
                                ),
                            )
                        );

                        $objPHPExcel->getActiveSheet()->getStyle('A5:A'.($highestRow+2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                        $objPHPExcel->getActiveSheet()->getStyle('A5:A'.($highestRow+2))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                        $objPHPExcel->getActiveSheet()->getStyle("A".($highestRow+2).":E".($highestRow+2))->getFont()->getColor()->setARGB('FF0000');

                        $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(16);
                        $objPHPExcel->getActiveSheet()->getStyle("A3:E".($highestRow))->getFont()->setSize(12);
                        $objPHPExcel->getActiveSheet()->getStyle("A".($highestRow+2).":E".($highestRow+2))->getFont()->setSize(14);
                        $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(16);
                        $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(20);
                        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(28);
                        $objPHPExcel->getActiveSheet()->getRowDimension('3')->setRowHeight(22);
                        $objPHPExcel->getActiveSheet()->getRowDimension('4')->setRowHeight(22);

                        $objPHPExcel->getActiveSheet()->freezePane('A5');
                        // Set properties
                    $objPHPExcel->getProperties()->setCreator("Cai Mep Trading")
                                    ->setLastModifiedBy($_SESSION['user_logined'])
                                    ->setTitle("Revenue Report")
                                    ->setSubject("Revenue Report")
                                    ->setDescription("Revenue Report.")
                                    ->setKeywords("Revenue Report")
                                    ->setCategory("Revenue Report");
                    $objPHPExcel->getActiveSheet()->setTitle("THÁNG ".($ngaybatdau+$z-$m));

                }

                $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

                header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
                header("Content-Disposition: attachment; filename= BẢNG LƯƠNG DOANH SỐ ".strtoupper($staff_sale->staff_name)."-".$ngaybatdau.'/'.$nam.'-'.$ngayketthuc.'/'.$namketthuc.".xlsx");
                header("Cache-Control: max-age=0");
                ob_clean();
                $objWriter->save("php://output");
            }
            else if ($this->registry->router->addition == null) {
                 

                

                $ngaybatdau = $this->registry->router->param_id;
                $ngayketthuc = $this->registry->router->order_by;
                $nam = $this->registry->router->page;
                $namketthuc = $this->registry->router->order;

                require("lib/Classes/PHPExcel/IOFactory.php");
                require("lib/Classes/PHPExcel.php");

                $objPHPExcel = new PHPExcel();

                $staff_model = $this->model->get('staffModel');
                $sales_model = $this->model->get('salesModel');

                if ($namketthuc==$nam) {
                    $vonglap = $ngayketthuc-$ngaybatdau;
                }
                elseif ($namketthuc-$nam == 1) {
                    $vonglap = (12-$ngaybatdau)+$ngayketthuc;
                }
                elseif ($namketthuc-$nam > 1) {
                    $vonglap = (12-$ngaybatdau)+$ngayketthuc+($namketthuc-$nam-1)*12;
                }

                $m = 0;
                $n = 0;
                for ($z=0; $z <= $vonglap; $z++) { 
                    if (($ngaybatdau+$z) >12 ) {
                        $m = 12;
                        $n = 1;
                    }
                    
                    $join = array('table'=>'customer, accounting','where'=>'sales.customer = customer.customer_id AND sales.code = accounting.accounting_code AND sales.comment = accounting.accounting_comment');
                    $data = array(
                        'where' => ' (sales_create_time LIKE "%'. ($ngaybatdau+$z-$m).'/'.($nam+$n).'%" AND accounting_create_time LIKE "%'. ($ngaybatdau+$z-$m).'/'.($nam+$n).'%" )',
                        );
                    $sale = $sales_model->getAllSales($data,$join);

                    $staff_sale = $staff_model->getAllStaff(array('where'=>'salary_create_time LIKE "%'.($ngaybatdau+$z-$m).'/'.($nam+$n).'%"'),array('table'=>'salary, sales','where'=>'staff.staff_id = salary.staff AND (staff_id = m OR staff_id = s OR staff_id = c) AND sales_create_time = salary_create_time GROUP BY staff_id'));
            

                    $staff = $staff_model->getAllStaff(array('where'=>'salary_create_time LIKE "%'.($ngaybatdau+$z-$m).'/'.($nam+$n).'%"'),array('table'=>'salary,sales','where'=>'staff.staff_id = salary.staff AND (staff_id = m OR staff_id = s OR staff_id = c) AND sales_create_time = salary_create_time'));
                    $staff_data = array();
                    foreach ($staff as $staff) {
                        $staff_data['staff_id'][$staff->salary_create_time][$staff->staff_id] = $staff->staff_id;
                        $staff_data['staff_name'][$staff->salary_create_time][$staff->staff_id] = $staff->staff_name;
                        $staff_data['basic_salary'][$staff->salary_create_time][$staff->staff_id] = $staff->basic_salary;
                    }


                    
                    $objPHPExcel->createSheet();
                    $index_worksheet = $z; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
                    $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A1', 'BẢNG TÍNH LƯƠNG DOANH SỐ '.($ngaybatdau+$z-$m).'/'.($nam+$n))
                       ->setCellValue('A3', 'STT')
                       ->setCellValue('B3', 'Code')
                       ->setCellValue('C3', 'Khách hàng')
                       ->setCellValue('D3', 'Diễn giải')
                       ->setCellValue('E3', 'Ngày thanh toán')
                       ->setCellValue('F3', 'MSC')
                       ->setCellValue('I3', 'Tổng hợp doanh thu')
                       ->setCellValue('K3', 'Tổng hợp chi phí')
                       ->setCellValue('M3', 'Lợi nhuận')
                       ->setCellValue('F4', 'M')
                       ->setCellValue('G4', 'S')
                       ->setCellValue('H4', 'C')
                       ->setCellValue('I4', 'Kế toán')
                       ->setCellValue('J4', 'Sale')
                       ->setCellValue('K4', 'Kế toán')
                       ->setCellValue('L4', 'Sale');


                    

                    
                    
                    $tongthu = 0;
                    $tongketoan = 0;
                    $tongsale = 0;
                    $tongloinhuan = 0;
                    $bonus_m = 0;
                    $bonus_s = 0;
                    $bonus_c = 0;
                    $total_bonus = array();
                    $arr_msc = array();

                    if ($sale) {

                        $hang = 5;
                        $i=1;
                        foreach ($sale as $row) {
                            //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );
                             $objPHPExcel->setActiveSheetIndex($index_worksheet)
                                ->setCellValue('A' . $hang, $i++)
                                ->setCellValueExplicit('B' . $hang, $row->code)
                                ->setCellValue('C' . $hang, $row->customer_name)
                                ->setCellValue('D' . $hang, $row->comment)
                                ->setCellValue('E' . $hang, $row->accounting_payment_date." - ".$row->accounting_bank)
                                ->setCellValue('F' . $hang, (isset($staff_data['staff_id'][$row->sales_create_time][$row->m]) && $row->m==$staff_data['staff_id'][$row->sales_create_time][$row->m])?$staff_data['staff_name'][$row->sales_create_time][$row->m]:null)
                                ->setCellValue('G' . $hang, (isset($staff_data['staff_id'][$row->sales_create_time][$row->s]) && $row->s==$staff_data['staff_id'][$row->sales_create_time][$row->s])?$staff_data['staff_name'][$row->sales_create_time][$row->s]:null)
                                ->setCellValue('H' . $hang, (isset($staff_data['staff_id'][$row->sales_create_time][$row->c]) && $row->c==$staff_data['staff_id'][$row->sales_create_time][$row->c])?$staff_data['staff_name'][$row->sales_create_time][$row->c]:null)
                                ->setCellValue('I' . $hang, $row->accounting_amount)
                                ->setCellValue('J' . $hang, $row->revenue)
                                ->setCellValue('K' . $hang, $row->accounting_cost)
                                ->setCellValue('L' . $hang, $row->cost)
                                ->setCellValue('M' . $hang, '=J'.$hang.'-L'.$hang.'');
                             $hang++;

                             $tongthu += $row->revenue;
                             $tongketoan += $row->accounting_cost;
                             $tongsale += $row->cost;
                             $tongloinhuan += $row->profit;

                             /*******/
                            $arr_msc[$row->sales_create_time][$row->m]['m'][$row->sales_id][] = $row->profit;
                            $arr_msc[$row->sales_create_time][$row->s]['s'][$row->sales_id][] = $row->profit;
                            $arr_msc[$row->sales_create_time][$row->c]['c'][$row->sales_id][] = $row->profit;
                            /********/
                            $staff_msc[$row->sales_create_time][$row->m]['m'][$row->sales_id] = $row->profit;
                            $staff_msc[$row->sales_create_time][$row->s]['s'][$row->sales_id] = $row->profit;
                            $staff_msc[$row->sales_create_time][$row->c]['c'][$row->sales_id] = $row->profit;

                            }

                        $array_sum = 0;
                        $total_bonus = array();

                            $m_sum = array();
                            $s_sum = array();
                            $c_sum = array();
                            $salary_arr = array();

                            $thuong_m = 0;
                            $thuong_s = 0;
                            $thuong_c = 0;

                            $arr_thuong_m = array();
                            $arr_thuong_s = array();
                            $arr_thuong_c = array();

                        foreach ($arr_msc as $thang => $mang) {
                            
                            
                            foreach ($mang as $key => $value) {
                              //var_dump($value['m']);die();
                                $m_sum[$thang][$key] = 0;
                                $s_sum[$thang][$key] = 0;
                                $c_sum[$thang][$key] = 0;
                                $total_bonus[$thang][$key] = 0;

                                $arr_thuong_m[$key][$thang] = 0;
                                $arr_thuong_s[$key][$thang] = 0;
                                $arr_thuong_c[$key][$thang] = 0;

                              if (isset($value['m'])) {
                                foreach ($value['m'] as $key1 => $value1) {
                                    $m_sum[$thang][$key] += array_sum($value1);
                                    
                                }
                              }
                              if (isset($value['s'])) {
                                foreach ($value['s'] as $key2 => $value2) {
                                    $s_sum[$thang][$key] += array_sum($value2);
                                    
                                }
                              }
                              if (isset($value['c'])) {
                                foreach ($value['c'] as $key3 => $value3) {
                                    $c_sum[$thang][$key] += array_sum($value3);
                                    
                                }
                              }
                              
                              //$total_bonus[$key] = (isset($m_bonus[$key])?(array_sum($m_bonus[$key])>0?array_sum($m_bonus[$key]):0):0)+(isset($s_bonus[$key])?(array_sum($s_bonus[$key])>0?array_sum($s_bonus[$key]):0):0)+(isset($c_bonus[$key])?(array_sum($c_bonus[$key])>0?array_sum($c_bonus[$key]):0):0);
                              //$array_sum[] = $total_bonus[$key];

                              $thuong_m = isset($staff_data['basic_salary'][$thang][$key]) ? (($m_sum[$thang][$key]-(3*$staff_data['basic_salary'][$thang][$key]))*10/100) : 0;
                              $thuong_s = isset($staff_data['basic_salary'][$thang][$key]) ? (($s_sum[$thang][$key]-(3*$staff_data['basic_salary'][$thang][$key]))*10/100) : 0;
                              $thuong_c = isset($staff_data['basic_salary'][$thang][$key]) ? (($c_sum[$thang][$key]-(3*$staff_data['basic_salary'][$thang][$key]))*10/100) : 0;


                              $arr_thuong_m[$key][$thang] += ($thuong_m > 0 ? $thuong_m : 0);
                              $arr_thuong_s[$key][$thang] += ($thuong_s > 0 ? $thuong_s : 0);
                              $arr_thuong_c[$key][$thang] += ($thuong_c > 0 ? $thuong_c : 0);


                              $total_bonus[$thang][$key] += ($thuong_m > 0 ? $thuong_m : 0) + ($thuong_s > 0 ? $thuong_s : 0) + ($thuong_c > 0 ? $thuong_c : 0);
                              
                            }
                            //$array_sum[] = array_sum($total_bonus);
                            //var_dump($total_bonus);
                            $array_sum += array_sum($total_bonus[$thang]);
                        }

                  }

                    $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();

                    $highestRow ++;

                    // Set properties
                    $objPHPExcel->getProperties()->setCreator("Cai Mep Trading")
                                    ->setLastModifiedBy($_SESSION['user_logined'])
                                    ->setTitle("Revenue Report")
                                    ->setSubject("Revenue Report")
                                    ->setDescription("Revenue Report.")
                                    ->setKeywords("Revenue Report")
                                    ->setCategory("Revenue Report");
                    $objPHPExcel->getActiveSheet()->setTitle("THÁNG ".($ngaybatdau+$z-$m));

                    $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('A3')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('B3')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('C3')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('D3')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('E3')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('F3')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('G4')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('H4')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('I3')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('I4')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('J4')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('K3')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('M3')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('L4')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('K4')->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('F4')->getFont()->setBold(true);


                    $objPHPExcel->getActiveSheet()->mergeCells('A1:M1');
                    $objPHPExcel->getActiveSheet()->mergeCells('A2:M2');

                    $objPHPExcel->getActiveSheet()->mergeCells('A3:A4');
                    $objPHPExcel->getActiveSheet()->mergeCells('B3:B4');
                    $objPHPExcel->getActiveSheet()->mergeCells('C3:C4');
                    $objPHPExcel->getActiveSheet()->mergeCells('D3:D4');
                    $objPHPExcel->getActiveSheet()->mergeCells('E3:E4');
                    $objPHPExcel->getActiveSheet()->mergeCells('F3:H3');
                    $objPHPExcel->getActiveSheet()->mergeCells('I3:J3');
                    $objPHPExcel->getActiveSheet()->mergeCells('K3:L3');
                    $objPHPExcel->getActiveSheet()->mergeCells('M3:M4');

                    

                    $objPHPExcel->getActiveSheet()->getStyle('I4:I'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");
                    $objPHPExcel->getActiveSheet()->getStyle('J4:J'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");
                    $objPHPExcel->getActiveSheet()->getStyle('L4:L'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");
                    $objPHPExcel->getActiveSheet()->getStyle('M4:M'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");
                    $objPHPExcel->getActiveSheet()->getStyle('K4:K'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");


                    $objPHPExcel->setActiveSheetIndex($index_worksheet)
                       ->setCellValue('A'.$highestRow, 'TỔNG CỘNG')
                       ->setCellValue('I'.$highestRow, '=SUM(I5:I'.($highestRow-1).')')
                       ->setCellValue('J'.$highestRow, '=SUM(J5:J'.($highestRow-1).')')
                       ->setCellValue('K'.$highestRow, '=SUM(K5:K'.($highestRow-1).')')
                       ->setCellValue('L'.$highestRow, '=SUM(L5:L'.($highestRow-1).')')
                       ->setCellValue('M'.$highestRow, '=SUM(M5:M'.($highestRow-1).')');

                    $objPHPExcel->setActiveSheetIndex($index_worksheet)
                       ->setCellValue('A'.($highestRow+1), 'THƯỞNG THÁNG')
                       ->setCellValue('I'.($highestRow+1), $array_sum);

                    $objPHPExcel->getActiveSheet()->mergeCells('A'.$highestRow.':H'.$highestRow);
                    $objPHPExcel->getActiveSheet()->mergeCells('A'.($highestRow+1).':H'.($highestRow+1));
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$highestRow)->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+1))->getFont()->setBold(true);

                    
                    $objPHPExcel->getActiveSheet()->getStyle('A1:M'.($highestRow+1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A1:M'.($highestRow+1))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                    $objPHPExcel->getActiveSheet()->getStyle('I5:I'.($highestRow+1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                    $objPHPExcel->getActiveSheet()->getStyle('J5:J'.($highestRow+1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                    $objPHPExcel->getActiveSheet()->getStyle('K5:K'.($highestRow+1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                    $objPHPExcel->getActiveSheet()->getStyle('L5:L'.($highestRow+1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                    $objPHPExcel->getActiveSheet()->getStyle('M5:M'.($highestRow+1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                    $objPHPExcel->getActiveSheet()->getStyle('C5:E'.($highestRow+1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

                    $objPHPExcel->getActiveSheet()->getStyle('A3:M4')->getFont()->getColor()->setARGB('FF0000');
                    $objPHPExcel->getActiveSheet()->getStyle('A3:M4')->getFill()->getStartColor()->setARGB('FFFF00');
                    $objPHPExcel->getActiveSheet()->getStyle('A3:M4')->getAlignment()->setWrapText(true);
                    $objPHPExcel->getActiveSheet()->getStyle('A3:M4')->applyFromArray(
                        array(
                            'borders' => array(
                                'allborders' => array(
                                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                                    'color' => array('argb' => '000000'),
                                ),
                            ),
                            'fill' => array(
                                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                'color' => array('rgb' => 'FFFF00')
                            )
                        )
                    );
                    $objPHPExcel->getActiveSheet()->getStyle('I'.$highestRow.':M'.$highestRow)->applyFromArray(
                        array(
                            'borders' => array(
                                'allborders' => array(
                                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                                    'color' => array('argb' => '000000'),
                                ),
                            ),
                            'fill' => array(
                                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                'color' => array('rgb' => '00E0FF')
                            )
                        )
                    );
                    $objPHPExcel->getActiveSheet()->getStyle('A3:M'.($highestRow+1))->applyFromArray(
                        array(
                            'borders' => array(
                                'outline' => array(
                                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                                    'color' => array('argb' => '000000'),
                                ),
                            ),
                        )
                    );
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$highestRow)->getFont()->getColor()->setARGB('FF0000');
                    $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+1))->getFont()->getColor()->setARGB('FF0000');
                    $objPHPExcel->getActiveSheet()->getStyle('I'.($highestRow+1))->getFont()->getColor()->setARGB('FF0000');
                    $objPHPExcel->getActiveSheet()->getStyle('I'.$highestRow.':M'.$highestRow)->getFill()->getStartColor()->setARGB('00E0FF');
                    $objPHPExcel->getActiveSheet()->getStyle('I'.$highestRow.':M'.$highestRow)->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('I'.($highestRow+1))->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('I'.($highestRow+1))->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");

                    $objPHPExcel->getActiveSheet()->getStyle("A1:M".($highestRow+1))->getFont()->setName('Times New Roman');
                    $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(16);
                    $objPHPExcel->getActiveSheet()->getStyle("A3:M4")->getFont()->setSize(13);
                    $objPHPExcel->getActiveSheet()->getStyle("A".$highestRow.":M".($highestRow+1))->getFont()->setSize(12);

                    $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(16);
                    $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(20);
                    $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(28);
                    $objPHPExcel->getActiveSheet()->getRowDimension('3')->setRowHeight(25);
                    $objPHPExcel->getActiveSheet()->getRowDimension('4')->setRowHeight(25);

                    $objPHPExcel->getActiveSheet()->freezePane('A5');

                    

                    foreach ($staff_sale as $staff_sale) {
                        $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();
                        $objPHPExcel->setActiveSheetIndex($index_worksheet)
                           ->setCellValue('A'.($highestRow+3), strtoupper($staff_sale->staff_name));

                        $objPHPExcel->setActiveSheetIndex($index_worksheet)
                           ->setCellValue('A'.($highestRow+4), 'STT')
                           ->setCellValue('B'.($highestRow+4), 'CODE')
                           ->setCellValue('C'.($highestRow+4), 'MSC')
                           ->setCellValue('C'.($highestRow+5), 'M')
                           ->setCellValue('D'.($highestRow+5), 'S')
                           ->setCellValue('E'.($highestRow+5), 'C');

                        
                        $objPHPExcel->getActiveSheet()->getRowDimension($highestRow+3)->setRowHeight(28);
                        $objPHPExcel->getActiveSheet()->getRowDimension($highestRow+4)->setRowHeight(22);
                        $objPHPExcel->getActiveSheet()->getRowDimension($highestRow+5)->setRowHeight(22);
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+3))->getFont()->setSize(14);
                        $objPHPExcel->getActiveSheet()->mergeCells('A'.($highestRow+3).':E'.($highestRow+3).'');
                        $objPHPExcel->getActiveSheet()->mergeCells('A'.($highestRow+4).':A'.($highestRow+5).'');
                        $objPHPExcel->getActiveSheet()->mergeCells('B'.($highestRow+4).':B'.($highestRow+5).'');
                        $objPHPExcel->getActiveSheet()->mergeCells('C'.($highestRow+4).':E'.($highestRow+4).'');
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+3).':E'.($highestRow+5))->getFont()->setBold(true);
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+3).':E'.($highestRow+5))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+3).':E'.($highestRow+5))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+4).':E'.($highestRow+5))->applyFromArray(
                            array(
                                'borders' => array(
                                    'allborders' => array(
                                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                                        'color' => array('argb' => '000000'),
                                    ),
                                ),
                                'fill' => array(
                                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                    'color' => array('rgb' => '00E0FF')
                                )
                            )
                        );


                        $stt = 1;
                        $hang2 = $highestRow+6;
                        $hang3 = $highestRow+4;
                        foreach ($sale as $sale2) {
                            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                                ->setCellValue('A' . $hang2, $stt++)
                                ->setCellValueExplicit('B' . $hang2, $sale2->code)
                                ->setCellValue('C' . $hang2, isset($staff_msc[$sale2->sales_create_time][$staff_sale->staff_id]['m'][$sale2->sales_id])?$staff_msc[$sale2->sales_create_time][$staff_sale->staff_id]['m'][$sale2->sales_id]:null)
                                ->setCellValue('D' . $hang2, isset($staff_msc[$sale2->sales_create_time][$staff_sale->staff_id]['s'][$sale2->sales_id])?$staff_msc[$sale2->sales_create_time][$staff_sale->staff_id]['s'][$sale2->sales_id]:null)
                                ->setCellValue('E' . $hang2, isset($staff_msc[$sale2->sales_create_time][$staff_sale->staff_id]['c'][$sale2->sales_id])?$staff_msc[$sale2->sales_create_time][$staff_sale->staff_id]['c'][$sale2->sales_id]:null);
                             $hang2++;
                        }

                        $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();
                        $objPHPExcel->setActiveSheetIndex($index_worksheet)
                           ->setCellValue('C'.($highestRow+1), array_sum($arr_thuong_m[$staff_sale->staff_id]))
                           ->setCellValue('D'.($highestRow+1), array_sum($arr_thuong_s[$staff_sale->staff_id]))
                           ->setCellValue('E'.($highestRow+1), array_sum($arr_thuong_c[$staff_sale->staff_id]))
                           ->setCellValue('A'.($highestRow+2), 'THƯỞNG THÁNG')
                           ->setCellValue('C'.($highestRow+2), '=SUM(C'.($highestRow+1).':E'.($highestRow+1).')');

                        $objPHPExcel->getActiveSheet()->mergeCells('A'.($highestRow+1).':B'.($highestRow+1).'');
                        $objPHPExcel->getActiveSheet()->mergeCells('A'.($highestRow+2).':B'.($highestRow+2).'');
                        $objPHPExcel->getActiveSheet()->mergeCells('C'.($highestRow+2).':E'.($highestRow+2).'');
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+2).':E'.($highestRow+2))->getFont()->setSize(13);
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+1).':E'.($highestRow+2))->getFont()->setBold(true);
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+1).':E'.($highestRow+2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+1).':E'.($highestRow+2))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                        $objPHPExcel->getActiveSheet()->getStyle('C'.($hang3+1).':E'.($highestRow+2))->getNumberFormat()->setFormatCode("#,##0_);[Red](#,##0)");
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+2).':E'.($highestRow+2))->getFont()->getColor()->setARGB('FF0000');
                        $objPHPExcel->getActiveSheet()->getStyle('A'.($highestRow+1).':E'.($highestRow+2))->applyFromArray(
                            array(
                                'borders' => array(
                                    'outline' => array(
                                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                                        'color' => array('argb' => '000000'),
                                    ),
                                ),
                            )
                        );
                        $objPHPExcel->getActiveSheet()->getStyle('A'.$hang3.':E'.($highestRow+2))->applyFromArray(
                            array(
                                'borders' => array(
                                    'outline' => array(
                                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                                        'color' => array('argb' => '000000'),
                                    ),
                                ),
                            )
                        );
                        $objPHPExcel->getActiveSheet()->getStyle("A".$hang3.":E".($highestRow+2))->getFont()->setName('Times New Roman');
                    }

                    

                }

                //$objPHPExcel->setActiveSheetIndex(0);



                

                $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

                header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
                header("Content-Disposition: attachment; filename= BẢNG LƯƠNG DOANH SỐ ".$ngaybatdau.'/'.$nam.'-'.$ngayketthuc.'/'.$namketthuc.".xlsx");
                header("Cache-Control: max-age=0");
                ob_clean();
                $objWriter->save("php://output");
            }
        }
    }

    

    public function view() {
        
        $this->view->show('revenue/view');
    }

}
?>