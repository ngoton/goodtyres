<?php

Class phoneallowanceModel Extends baseModel {
	protected $table = "phone_allowance";

	public function getAllAllowance($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createAllowance($data) 
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
            'Allowance' => $data['Allowance'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateAllowance($data,$where) 
    {    
        if ($this->getAllowanceByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Allowance' => $data['Allowance'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteAllowance($id){
    	if ($this->getAllowance($id)) {
    		return $this->delete($this->table,array('phone_allowance_id'=>$id));
    	}
    }
    public function getAllowance($id){
        return $this->getByID($this->table,$id);
    }
    public function getAllowanceByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllAllowanceByWhere($id){
        return $this->query('SELECT * FROM phone_allowance WHERE phone_allowance_id != '.$id);
    }
    public function getLastAllowance(){
        return $this->getLast($this->table);
    }
    public function queryAllowance($sql){
        return $this->query($sql);
    }
}
?>