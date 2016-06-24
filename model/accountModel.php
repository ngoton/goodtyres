<?php

Class accountModel Extends baseModel {
	protected $table = "account";

	public function getAllAccount($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createAccount($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    public function updateAccount($data,$id) 
    {    
        if ($this->getAccountByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteAccount($id){
    	if ($this->getAccount($id)) {
    		return $this->delete($this->table,array('account_id'=>$id));
    	}
    }
    public function getAccount($id){
    	return $this->getByID($this->table,$id);
    }
    public function getAccountByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastAccount(){
        return $this->getLast($this->table);
    }
    public function queryAccount($sql){
        return $this->query($sql);
    }
}
?>