<?php

Class shipchargeModel Extends baseModel {
	protected $table = "shipcharge";

	public function getAllShipcharge($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createShipcharge($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    public function updateShipcharge($data,$id) 
    {    
        if ($this->getShipchargeByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteShipcharge($id){
    	if ($this->getShipcharge($id)) {
    		return $this->delete($this->table,array('shipcharge_id'=>$id));
    	}
    }
    public function getShipcharge($id){
    	return $this->getByID($this->table,$id);
    }
    public function getShipchargeByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastShipcharge(){
        return $this->getLast($this->table);
    }
    public function getShipchargeByField($column,$where){
        return $this->query('SELECT '.$column.' FROM shipcharge WHERE '.$where);
    }
}
?>