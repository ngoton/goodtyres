<?php

Class photoModel Extends baseModel {
	protected $table = "photo";

	public function getAllPhoto($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createPhoto($data) 
    {    
        /*$data = array(
        	'staff_id' => $data['staff_id'],
        	'staff_name' => $data['staff_name'],
        	'staff_birth' => $data['staff_birth'],
        	'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'bank' => $data['bank'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updatePhoto($data,$where) 
    {    
        if ($this->getPhotoByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'bank' => $data['bank'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deletePhoto($id){
    	if ($this->getPhoto($id)) {
    		return $this->delete($this->table,array('photo_id'=>$id));
    	}
    }
    public function getPhoto($id){
        return $this->getByID($this->table,$id);
    }
    public function getPhotoByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllPhotoByWhere($id){
        return $this->query('SELECT * FROM photo WHERE photo_id != '.$id);
    }
    public function queryPhoto($sql){
        return $this->query($sql);
    }
    public function getLastPhoto(){
        return $this->getLast($this->table);
    }
}
?>