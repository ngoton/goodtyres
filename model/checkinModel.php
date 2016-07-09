<?php

Class checkinModel Extends baseModel {
	protected $table = "checkin";

	public function getAllCheckin($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createCheckin($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    public function updateCheckin($data,$id) 
    {    
        if ($this->getCheckinByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteCheckin($id){
    	if ($this->getCheckin($id)) {
    		return $this->delete($this->table,array('checkin_id'=>$id));
    	}
    }
    public function getCheckin($id){
    	return $this->getByID($this->table,$id);
    }
    public function getCheckinByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastCheckin(){
        return $this->getLast($this->table);
    }
    public function queryCheckin($sql){
        return $this->query($sql);
    }
}
?>