<?php

Class codeModel Extends baseModel {
	protected $table = "code";

	public function getAllCode($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createCode($data) 
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
            'Code' => $data['Code'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateCode($data,$where) 
    {    
        if ($this->getCodeByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Code' => $data['Code'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteCode($id){
    	if ($this->getCode($id)) {
    		return $this->delete($this->table,array('code_id'=>$id));
    	}
    }
    public function getCode($id){
        return $this->getByID($this->table,$id);
    }
    public function getCodeByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllCodeByWhere($id){
        return $this->query('SELECT * FROM code WHERE code_id != '.$id);
    }
    public function getLastCode(){
        return $this->getLast($this->table);
    }
    public function queryCode($sql){
        return $this->query($sql);
    }
}
?>