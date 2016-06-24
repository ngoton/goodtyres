<?php

Class positionsalaryModel Extends baseModel {
	protected $table = "position_salary";

	public function getAllSalary($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createSalary($data) 
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
            'Salary' => $data['Salary'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateSalary($data,$where) 
    {    
        if ($this->getSalaryByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Salary' => $data['Salary'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteSalary($id){
    	if ($this->getSalary($id)) {
    		return $this->delete($this->table,array('position_salary_id'=>$id));
    	}
    }
    public function getSalary($id){
        return $this->getByID($this->table,$id);
    }
    public function getSalaryByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllSalaryByWhere($id){
        return $this->query('SELECT * FROM position_salary WHERE position_salary_id != '.$id);
    }
    public function getLastSalary(){
        return $this->getLast($this->table);
    }
    public function querySalary($sql){
        return $this->query($sql);
    }
}
?>