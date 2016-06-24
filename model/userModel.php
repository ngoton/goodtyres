<?php

Class userModel Extends baseModel {
	protected $table = "user";

	public function getAllUser($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createUser($data) 
    {    
        /*$data = array(
        	'username' => $data['username'],
        	'password' => $data['password'],
        	'create_time' => $data['create_time'],
        	'role' => $data['role'],
        	);*/
        return $this->insert($this->table,$data);
    }
    public function updateUser($data,$id) 
    {    
        if ($this->getUserByWhere($id)) {
        	/*$data = array(
	        	'username' => $data['username'],
	        	'password' => $data['password'],
	        	'create_time' => $data['create_time'],
	        	'role' => $data['role'],
	        	);*/
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteUser($id){
    	if ($this->getUser($id)) {
    		return $this->delete($this->table,array('user_id'=>$id));
    	}
    }
    public function getUser($id){
    	return $this->getByID($this->table,$id);
    }
    public function getUserByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getUserByUsername($username){
    	return $this->getByWhere($this->table,array('username'=>$username));
    }
    public function getLastUser(){
        return $this->getLast($this->table);
    }
}
?>