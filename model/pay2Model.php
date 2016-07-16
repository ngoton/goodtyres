<?php

Class pay2Model Extends baseModel {
	protected $table = "pay";

	public function getAllCosts($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createCosts($data) 
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
            'Costs' => $data['Costs'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function createCosts3($data) 
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
            'Costs' => $data['Costs'],
            'account' => $data['account'],
            );*/

        return $this->insert3($this->table,$data);
    }
    public function updateCosts($data,$where) 
    {    
        if ($this->getCostsByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Costs' => $data['Costs'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteCosts($id){
    	if ($this->getCosts($id)) {
    		return $this->delete($this->table,array('pay_id'=>$id));
    	}
    }
    public function getCosts($id){
        return $this->getByID($this->table,$id);
    }
    public function getCostsByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllCostsByWhere($id){
        return $this->query('SELECT * FROM pay WHERE pay_id != '.$id);
    }
    public function getLastCosts(){
        return $this->getLast($this->table);
    }
    public function getLastCosts3(){
        return $this->getLast3($this->table);
    }
    public function queryCosts($sql){
        return $this->query($sql);
    }
}
?>