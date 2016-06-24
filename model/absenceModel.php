<?php

Class absenceModel Extends baseModel {
	protected $table = "absence";

	public function getAllAbsence($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createAbsence($data) 
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
            'Absence' => $data['Absence'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateAbsence($data,$where) 
    {    
        if ($this->getAbsenceByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Absence' => $data['Absence'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteAbsence($id){
    	if ($this->getAbsence($id)) {
    		return $this->delete($this->table,array('absence_id'=>$id));
    	}
    }
    public function getAbsence($id){
        return $this->getByID($this->table,$id);
    }
    public function getAbsenceByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllAbsenceByWhere($id){
        return $this->query('SELECT * FROM absence WHERE absence_id != '.$id);
    }
    public function getLastAbsence(){
        return $this->getLast($this->table);
    }
    public function queryAbsence($sql){
        return $this->query($sql);
    }
}
?>