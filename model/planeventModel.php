<?php

Class planeventModel Extends baseModel {
	protected $table = "plan_event";

	public function getAllPlan($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createPlan($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    public function updatePlan($data,$id) 
    {    
        if ($this->getPlanByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deletePlan($id){
    	if ($this->getPlan($id)) {
    		return $this->delete($this->table,array('plan_event_id'=>$id));
    	}
    }
    public function getPlan($id){
    	return $this->getByID($this->table,$id);
    }
    public function getPlanByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastPlan(){
        return $this->getLast($this->table);
    }
    public function queryPlan($sql){
        return $this->query($sql);
    }
}
?>