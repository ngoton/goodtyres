<?php

Class locationModel Extends baseModel {
	protected $table = "location";

	public function getAllLocation($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createLocation($data) 
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
    public function updateLocation($data,$where) 
    {    
        if ($this->getLocationByWhere($where)) {
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
    public function deleteLocation($id){
    	if ($this->getLocation($id)) {
    		return $this->delete($this->table,array('location_id'=>$id));
    	}
    }
    public function getLocation($id){
        return $this->getByID($this->table,$id);
    }
    public function getLocationByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllLocationByWhere($id){
        return $this->query('SELECT * FROM location WHERE location_id != '.$id);
    }
    public function getLastLocation(){
        return $this->getLast($this->table);
    }
    public function checkLocation($id,$location_name,$district){
        return $this->query('SELECT * FROM location WHERE location_id != '.$id.' AND location_name = "'.$location_name.'" AND district = '.$district);
    }
}
?>