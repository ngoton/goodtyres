<?php

Class roleModel Extends baseModel {
	protected $table = "role";

	public function getAllRole($data = null) 
    {
        return $this->fetchAll($this->table,$data);
    }
    public function getRole($id){
        return $this->getByID($this->table,$id);
    }
    public function getAllRoleByWhere($id){
        return $this->query('SELECT * FROM role WHERE role_id != '.$id);
    }

}
?>