<?php

Class workModel Extends baseModel {
	protected $table = "work";

	public function getAllWork($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createWork($data) 
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
    public function updateWork($data,$where) 
    {    
        if ($this->getWorkByWhere($where)) {
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
    public function deleteWork($id){
    	if ($this->getWork($id)) {
    		return $this->delete($this->table,array('work_id'=>$id));
    	}
    }
    public function getWork($id){
        return $this->getByID($this->table,$id);
    }
    public function getWorkByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllWorkByWhere($id){
        return $this->query('SELECT * FROM work WHERE work_id != '.$id);
    }
    public function getLastWork(){
        return $this->getLast($this->table);
    }
    public function queryWork($sql){
        return $this->query($sql);
    }
}
?>