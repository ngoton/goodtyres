<?php

Class etransportModel Extends baseModel {
	protected $table = "e_transport";

	public function getAllTransport($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createTransport($data) 
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
    public function updateTransport($data,$where) 
    {    
        if ($this->getTransportByWhere($where)) {
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
    public function deleteTransport($id){
    	if ($this->getTransport($id)) {
    		return $this->delete($this->table,array('e_transport_id'=>$id));
    	}
    }
    public function getTransport($id){
        return $this->getByID($this->table,$id);
    }
    public function getTransportByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllTransportByWhere($id){
        return $this->query('SELECT * FROM e_transport WHERE e_transport_id != '.$id);
    }
    public function getLastTransport(){
        return $this->getLast($this->table);
    }
    public function checkTransport($date, $from, $to, $customer, $sale){
        return $this->query('SELECT * FROM e_transport WHERE e_transport_date = '.$date.' AND e_loc_from = '.$from.' AND e_loc_to = '.$to.' AND ( customer = '.$customer.' OR sale = '.$sale.' )');
    }
}
?>