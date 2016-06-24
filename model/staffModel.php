<?php

Class staffModel Extends baseModel {
	protected $table = "staff";

	public function getAllStaff($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createStaff($data) 
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
            'bank' => $data['bank'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateStaff($data,$where) 
    {    
        if ($this->getStaffByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'bank' => $data['bank'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteStaff($id){
    	if ($this->getStaff($id)) {
    		return $this->delete($this->table,array('staff_id'=>$id));
    	}
    }
    public function getStaff($id){
        return $this->getByID($this->table,$id);
    }
    public function getStaffByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllStaffByWhere($id){
        return $this->query('SELECT * FROM staff WHERE staff_id != '.$id);
    }
    public function getLastStaff(){
        return $this->getLast($this->table);
    }
    public function getStaffByAccount($id){
        return $this->getByWhere($this->table,array('account'=>$id));
    }
}
?>