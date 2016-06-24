<?php

Class reportModel Extends baseModel {
	protected $table = "report";

	public function getAllReport($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createReport($data) 
    {    
        /*$data = array(
        	'staff_id' => $data['staff_id'],
        	'staff_name' => $data['staff_name'],
        	'staff_birth' => $data['staff_birth'],
        	'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Report' => $data['Report'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateReport($data,$where) 
    {    
        if ($this->getReportByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Report' => $data['Report'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteReport($id){
    	if ($this->getReport($id)) {
    		return $this->delete($this->table,array('report_id'=>$id));
    	}
    }
    public function getReport($id){
        return $this->getByID($this->table,$id);
    }
    public function getReportByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllReportByWhere($id){
        return $this->query('SELECT * FROM report WHERE report_id != '.$id);
    }
    public function getLastReport(){
        return $this->getLast($this->table);
    }
    public function queryReport($sql){
        return $this->query($sql);
    }
}
?>