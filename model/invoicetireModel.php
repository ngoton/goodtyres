<?php

Class invoicetireModel Extends baseModel {
	protected $table = "invoice_tire";

	public function getAllInvoice($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createInvoice($data) 
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
            'Invoice' => $data['Invoice'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateInvoice($data,$where) 
    {    
        if ($this->getInvoiceByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Invoice' => $data['Invoice'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteInvoice($id){
    	if ($this->getInvoice($id)) {
    		return $this->delete($this->table,array('invoice_id'=>$id));
    	}
    }
    public function getInvoice($id){
        return $this->getByID($this->table,$id);
    }
    public function getInvoiceByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllInvoiceByWhere($id){
        return $this->query('SELECT * FROM invoice_tire WHERE invoice_tire_id != '.$id);
    }
    public function getLastInvoice(){
        return $this->getLast($this->table);
    }
    public function queryInvoice($sql){
        return $this->query($sql);
    }
}
?>