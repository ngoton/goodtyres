<?php

Class obtainModel Extends baseModel {
	protected $table = "obtain";

	public function getAllObtain($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createObtain($data) 
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
            'Obtain' => $data['Obtain'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateObtain($data,$where) 
    {    
        if ($this->getObtainByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Obtain' => $data['Obtain'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteObtain($id){
    	if ($this->getObtain($id)) {
    		return $this->delete($this->table,array('obtain_id'=>$id));
    	}
    }
    public function getObtain($id){
        return $this->getByID($this->table,$id);
    }
    public function getObtainByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllObtainByWhere($id){
        return $this->query('SELECT * FROM obtain WHERE obtain_id != '.$id);
    }
    public function getLastObtain(){
        return $this->getLast($this->table);
    }
    public function queryObtain($sql){
        return $this->query($sql);
    }
}
?>