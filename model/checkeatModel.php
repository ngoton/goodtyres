<?php

Class checkeatModel Extends baseModel {
	protected $table = "checkeat";

	public function getAllCheckeat($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createCheckeat($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    public function updateCheckeat($data,$id) 
    {    
        if ($this->getCheckeatByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteCheckeat($id){
    	if ($this->getCheckeat($id)) {
    		return $this->delete($this->table,array('checkeat_id'=>$id));
    	}
    }
    public function getCheckeat($id){
    	return $this->getByID($this->table,$id);
    }
    public function getCheckeatByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastCheckeat(){
        return $this->getLast($this->table);
    }
    public function queryCheckeat($sql){
        return $this->query($sql);
    }
}
?>