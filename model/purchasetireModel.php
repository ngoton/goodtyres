<?php

Class purchasetireModel Extends baseModel {
	protected $table = "purchase_tire";

	public function getAllTire($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createTire($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    public function updateTire($data,$id) 
    {    
        if ($this->getTireByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteTire($id){
    	if ($this->getTire($id)) {
    		return $this->delete($this->table,array('purchase_tire_id'=>$id));
    	}
    }
    public function getTire($id){
    	return $this->getByID($this->table,$id);
    }
    public function getTireByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastTire(){
        return $this->getLast($this->table);
    }
    public function queryTire($sql){
        return $this->query($sql);
    }
}
?>