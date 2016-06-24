<?php

Class vehicletypeModel Extends baseModel {
	protected $table = "vehicle_type";

	public function getAllVehicle($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createVehicle($data) 
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
            'Vehicle' => $data['Vehicle'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateVehicle($data,$where) 
    {    
        if ($this->getVehicleByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Vehicle' => $data['Vehicle'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteVehicle($id){
    	if ($this->getVehicle($id)) {
    		return $this->delete($this->table,array('vehicle_type_id'=>$id));
    	}
    }
    public function getVehicle($id){
        return $this->getByID($this->table,$id);
    }
    public function getVehicleByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllVehicleByWhere($id){
        return $this->query('SELECT * FROM vehicle_type WHERE vehicle_type_id != '.$id);
    }
    public function getLastVehicle(){
        return $this->getLast($this->table);
    }
    public function queryVehicle($sql){
        return $this->query($sql);
    }
}
?>