<?php

Class accountingModel Extends baseModel {
	protected $table = "accounting";

	public function getAllAccounting($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createAccounting($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    public function updateAccounting($data,$id) 
    {    
        if ($this->getAccountingByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteAccounting($id){
    	if ($this->getAccounting($id)) {
    		return $this->delete($this->table,array('accounting_id'=>$id));
    	}
    }
    public function getAccounting($id){
    	return $this->getByID($this->table,$id);
    }
    public function getAccountingByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastAccounting(){
        return $this->getLast($this->table);
    }
    public function getAccountingQuery($sql){
        return $this->query($sql);
    }
}
?>