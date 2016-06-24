<?php

Class bankModel Extends baseModel {
	protected $table = "bank";

	public function getAllBank($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createBank($data) 
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
    public function updateBank($data,$where) 
    {    
        if ($this->getBankByWhere($where)) {
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
    public function deleteBank($id){
    	if ($this->getBank($id)) {
    		return $this->delete($this->table,array('bank_id'=>$id));
    	}
    }
    public function getBank($id){
        return $this->getByID($this->table,$id);
    }
    public function getBankByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllBankByWhere($id){
        return $this->query('SELECT * FROM bank WHERE bank_id != '.$id);
    }
    public function getLastBank(){
        return $this->getLast($this->table);
    }
}
?>