<?php

Class surchargeModel Extends baseModel {
	protected $table = "surcharge";

	public function getAllSurcharge($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createSurcharge($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    public function updateSurcharge($data,$id) 
    {    
        if ($this->getSurchargeByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteSurcharge($id){
    	if ($this->getSurcharge($id)) {
    		return $this->delete($this->table,array('surcharge_id'=>$id));
    	}
    }
    public function getSurcharge($id){
    	return $this->getByID($this->table,$id);
    }
    public function getSurchargeByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastSurcharge(){
        return $this->getLast($this->table);
    }
    public function getSurchargeByField($column,$where){
        return $this->query('SELECT '.$column.' FROM surcharge WHERE '.$where);
    }
}
?>