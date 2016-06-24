<?php

Class shippingModel Extends baseModel {
	protected $table = "shipping";

	public function getAllShipping($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createShipping($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    public function updateShipping($data,$id) 
    {    
        if ($this->getShippingByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteShipping($id){
    	if ($this->getShipping($id)) {
    		return $this->delete($this->table,array('shipping_id'=>$id));
    	}
    }
    public function getShipping($id){
    	return $this->getByID($this->table,$id);
    }
    public function getShippingByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastShipping(){
        return $this->getLast($this->table);
    }
    public function getShippingByField($column,$where){
        return $this->query('SELECT '.$column.' FROM shipping WHERE '.$where);
    }
}
?>