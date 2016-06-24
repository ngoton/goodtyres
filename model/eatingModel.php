<?php

Class eatingModel Extends baseModel {
	protected $table = "eating";

	public function getAllEating($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createEating($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    public function updateEating($data,$id) 
    {    
        if ($this->getEatingByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteEating($id){
    	if ($this->getEating($id)) {
    		return $this->delete($this->table,array('eating_id'=>$id));
    	}
    }
    public function getEating($id){
    	return $this->getByID($this->table,$id);
    }
    public function getEatingByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastEating(){
        return $this->getLast($this->table);
    }
    public function queryEating($sql){
        return $this->query($sql);
    }
}
?>