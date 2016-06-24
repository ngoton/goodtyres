<?php

Class salesModel Extends baseModel {
	protected $table = "sales";

	public function getAllSales($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createSales($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    public function updateSales($data,$id) 
    {    
        if ($this->getSalesByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteSales($id){
    	if ($this->getSales($id)) {
    		return $this->delete($this->table,array('sales_id'=>$id));
    	}
    }
    public function getSales($id){
    	return $this->getByID($this->table,$id);
    }
    public function getSalesByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastSales(){
        return $this->getLast($this->table);
    }
    public function querySales($sql){
        return $this->query($sql);
    }
}
?>