<?php

Class receiptsModel Extends baseModel {
	protected $table = "receipts";

	public function getAllReceipts($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createReceipts($data) 
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
    public function updateReceipts($data,$where) 
    {    
        if ($this->getReceiptsByWhere($where)) {
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
    public function deleteReceipts($id){
    	if ($this->getReceipts($id)) {
    		return $this->delete($this->table,array('receipts_id'=>$id));
    	}
    }
    public function getReceipts($id){
        return $this->getByID($this->table,$id);
    }
    public function getReceiptsByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllReceiptsByWhere($id){
        return $this->query('SELECT * FROM receipts WHERE receipts_id != '.$id);
    }
    public function getLastReceipts(){
        return $this->getLast($this->table);
    }
}
?>