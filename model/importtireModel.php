<?php

Class importtireModel Extends baseModel {
	protected $table = "import_tire";

	public function getAllSale($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createSale($data) 
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
            'Sale' => $data['Sale'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateSale($data,$where) 
    {    
        if ($this->getSaleByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Sale' => $data['Sale'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteSale($id){
    	if ($this->getSale($id)) {
    		return $this->delete($this->table,array('import_tire_id'=>$id));
    	}
    }
    public function getSale($id){
        return $this->getByID($this->table,$id);
    }
    public function getSaleByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllSaleByWhere($id){
        return $this->query('SELECT * FROM import_tire WHERE import_tire_id != '.$id);
    }
    public function getLastSale(){
        return $this->getLast($this->table);
    }
}
?>