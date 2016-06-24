<?php

Class eventModel Extends baseModel {
	protected $table = "event";

	public function getAllEvent($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createEvent($data) 
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
            'Event' => $data['Event'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateEvent($data,$where) 
    {    
        if ($this->getEventByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Event' => $data['Event'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteEvent($id){
    	if ($this->getEvent($id)) {
    		return $this->delete($this->table,array('event_id'=>$id));
    	}
    }
    public function getEvent($id){
        return $this->getByID($this->table,$id);
    }
    public function getEventByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllEventByWhere($id){
        return $this->query('SELECT * FROM event WHERE event_id != '.$id);
    }
    public function getLastEvent(){
        return $this->getLast($this->table);
    }
    public function queryEvent($sql){
        return $this->query($sql);
    }
}
?>