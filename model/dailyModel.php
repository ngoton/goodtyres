<?php

Class dailyModel Extends baseModel {
	protected $table = "daily";

	public function getAllDaily($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createDaily($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    public function createDaily2($data) 
    {    
        
        return $this->insert2($this->table,$data);
    }
    public function createDaily3($data) 
    {    
        $this->createDaily2($data);
        $id_last = $this->getLastDaily2()->daily_id;
        $data['daily_id'] = $id_last;
        return $this->insert3($this->table,$data);
    }
    public function updateDaily($data,$id) 
    {    
        if ($this->getDailyByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteDaily($id){
    	if ($this->getDaily($id)) {
    		return $this->delete($this->table,array('daily_id'=>$id));
    	}
    }
    public function getDaily($id){
    	return $this->getByID($this->table,$id);
    }
    public function getDailyByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastDaily(){
        return $this->getLast($this->table);
    }
    public function getLastDaily2(){
        return $this->getLast2($this->table);
    }
    public function getLastDaily3(){
        return $this->getLast3($this->table);
    }
    public function queryDaily($sql){
        return $this->query($sql);
    }
}
?>