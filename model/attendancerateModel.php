<?php

Class attendancerateModel Extends baseModel {
	protected $table = "attendance_rate";

	public function getAllAttendance($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createAttendance($data) 
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
            'Attendance' => $data['Attendance'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateAttendance($data,$where) 
    {    
        if ($this->getAttendanceByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Attendance' => $data['Attendance'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteAttendance($id){
    	if ($this->getAttendance($id)) {
    		return $this->delete($this->table,array('attendance_rate_id'=>$id));
    	}
    }
    public function getAttendance($id){
        return $this->getByID($this->table,$id);
    }
    public function getAttendanceByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllAttendanceByWhere($id){
        return $this->query('SELECT * FROM attendance_rate WHERE attendance_rate_id != '.$id);
    }
    public function getLastAttendance(){
        return $this->getLast($this->table);
    }
    public function queryAttendance($sql){
        return $this->query($sql);
    }
}
?>