<?php

Class paymentrequestdetailModel Extends baseModel {
	protected $table = "payment_request_detail";

	public function getAllPayment($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createPayment($data) 
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
            'Payment' => $data['Payment'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updatePayment($data,$where) 
    {    
        if ($this->getPaymentByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Payment' => $data['Payment'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deletePayment($id){
    	if ($this->getPayment($id)) {
    		return $this->delete($this->table,array('payment_request_detail_id'=>$id));
    	}
    }
    public function getPayment($id){
        return $this->getByID($this->table,$id);
    }
    public function getPaymentByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllPaymentByWhere($id){
        return $this->query('SELECT * FROM payment_request_detail WHERE payment_request_detail_id != '.$id);
    }
    public function getLastPayment(){
        return $this->getLast($this->table);
    }
    public function queryPayment($sql){
        return $this->query($sql);
    }
}
?>