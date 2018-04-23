<?php

Class contactpersonModel Extends baseModel {
	protected $table = "contact_person";

	public function getAllCustomer($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createCustomer($data) 
    {    
        /*$data = array(
        	'Customer_id' => $data['Customer_id'],
        	'Customer_name' => $data['Customer_name'],
        	'Customer_birth' => $data['Customer_birth'],
        	'Customer_gender' => $data['Customer_gender'],
            'Customer_address' => $data['Customer_address'],
            'Customer_phone' => $data['Customer_phone'],
            'Customer_email' => $data['Customer_email'],
            'cmnd' => $data['cmnd'],
            'bank' => $data['bank'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateCustomer($data,$where) 
    {    
        if ($this->getCustomerByWhere($where)) {
        	/*$data = array(
            'Customer_id' => $data['Customer_id'],
            'Customer_name' => $data['Customer_name'],
            'Customer_birth' => $data['Customer_birth'],
            'Customer_gender' => $data['Customer_gender'],
            'Customer_address' => $data['Customer_address'],
            'Customer_phone' => $data['Customer_phone'],
            'Customer_email' => $data['Customer_email'],
            'cmnd' => $data['cmnd'],
            'bank' => $data['bank'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteCustomer($id){
    	if ($this->getCustomer($id)) {
    		return $this->delete($this->table,array('contact_person_id'=>$id));
    	}
    }
    public function getCustomer($id){
        return $this->getByID($this->table,$id);
    }
    public function getCustomerByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllCustomerByWhere($id){
        return $this->query('SELECT * FROM contact_person WHERE contact_person_id != '.$id);
    }
    public function getLastCustomer(){
        return $this->getLast($this->table);
    }
    public function queryCustomer($sql){
        return $this->query($sql);
    }
}
?>