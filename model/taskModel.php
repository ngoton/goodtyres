<?php

Class taskModel Extends baseModel {
	protected $table = "task";

	public function getAllTask($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createTask($data) 
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
    public function updateTask($data,$where) 
    {    
        if ($this->getTaskByWhere($where)) {
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
    public function deleteTask($id){
    	if ($this->getTask($id)) {
    		return $this->delete($this->table,array('task_id'=>$id));
    	}
    }
    public function getTask($id){
        return $this->getByID($this->table,$id);
    }
    public function getTaskByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllTaskByWhere($id){
        return $this->query('SELECT * FROM task WHERE task_id != '.$id);
    }
    public function getLastTask(){
        return $this->getLast($this->table);
    }
    public function queryTask($sql){
        return $this->query($sql);
    }
}
?>