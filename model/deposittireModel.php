<?php

Class deposittireModel Extends baseModel {
	protected $table = "deposit_tire";

	public function getAllDeposit($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createDeposit($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    
    public function updateDeposit($data,$id) 
    {    
        if ($this->getDepositByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteDeposit($id){
    	if ($this->getDeposit($id)) {
    		return $this->delete($this->table,array('deposit_tire_id'=>$id));
    	}
    }
    public function getDeposit($id){
    	return $this->getByID($this->table,$id);
    }
    public function getDepositByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastDeposit(){
        return $this->getLast($this->table);
    }
    public function queryDeposit($sql){
        return $this->query($sql);
    }
}
?>