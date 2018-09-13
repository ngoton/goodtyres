<?php

Class ordertirelockModel Extends baseModel {
	protected $table = "order_tire_lock";

	public function getAllTire($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createTire($data) 
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
            'Tire' => $data['Tire'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateTire($data,$where) 
    {    
        if ($this->getTireByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Tire' => $data['Tire'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteTire($id){
    	if ($this->getTire($id)) {
    		return $this->delete($this->table,array('order_tire_lock_id'=>$id));
    	}
    }
    public function getTire($id){
        return $this->getByID($this->table,$id);
    }
    public function getTireByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllTireByWhere($id){
        return $this->query('SELECT * FROM order_tire_lock WHERE order_tire_lock_id != '.$id);
    }
    public function getLastTire(){
        return $this->getLast($this->table);
    }
    public function queryTire($sql){
        return $this->query($sql);
    }
}
?>