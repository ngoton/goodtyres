<?php

Class commissionModel Extends baseModel {
	protected $table = "commission";

	public function getAllCommission($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createCommission($data) 
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
    public function updateCommission($data,$where) 
    {    
        if ($this->getCommissionByWhere($where)) {
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
    public function deleteCommission($id){
    	if ($this->getCommission($id)) {
    		return $this->delete($this->table,array('commission_id'=>$id));
    	}
    }
    public function getCommission($id){
        return $this->getByID($this->table,$id);
    }
    public function getCommissionByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllCommissionByWhere($id){
        return $this->query('SELECT * FROM commission WHERE commission_id != '.$id);
    }
    public function getLastCommission(){
        return $this->getLast($this->table);
    }
}
?>