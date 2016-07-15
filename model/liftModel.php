<?php

Class liftModel Extends baseModel {
	protected $table = "lift";

	public function getAllLift($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createLift($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    public function updateLift($data,$id) 
    {    
        if ($this->getLiftByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteLift($id){
    	if ($this->getLift($id)) {
    		return $this->delete($this->table,array('lift_id'=>$id));
    	}
    }
    public function getLift($id){
    	return $this->getByID($this->table,$id);
    }
    public function getLiftByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastLift(){
        return $this->getLast($this->table);
    }
    public function queryLift($sql){
        return $this->query($sql);
    }
}
?>