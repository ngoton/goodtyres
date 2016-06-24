<?php

Class handlingModel Extends baseModel {
	protected $table = "handling";

	public function getAllHandling($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createHandling($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    public function updateHandling($data,$id) 
    {    
        if ($this->getHandlingByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteHandling($id){
    	if ($this->getHandling($id)) {
    		return $this->delete($this->table,array('handling_id'=>$id));
    	}
    }
    public function getHandling($id){
    	return $this->getByID($this->table,$id);
    }
    public function getHandlingByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastHandling(){
        return $this->getLast($this->table);
    }
    public function getHandlingByField($column,$where){
        return $this->query('SELECT '.$column.' FROM handling WHERE '.$where);
    }
}
?>