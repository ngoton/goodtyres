<?php

Class manifestModel Extends baseModel {
	protected $table = "manifest";

	public function getAllManifest($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createManifest($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    public function updateManifest($data,$id) 
    {    
        if ($this->getManifestByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteManifest($id){
    	if ($this->getManifest($id)) {
    		return $this->delete($this->table,array('manifest_id'=>$id));
    	}
    }
    public function getManifest($id){
    	return $this->getByID($this->table,$id);
    }
    public function getManifestByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastManifest(){
        return $this->getLast($this->table);
    }
    public function getManifestByField($column,$where){
        return $this->query('SELECT '.$column.' FROM manifest WHERE '.$where);
    }
}
?>