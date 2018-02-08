<?php

Class importtireportModel Extends baseModel {
	protected $table = "import_tire_port";

	public function getAllImport($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createImport($data) 
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
            'Import' => $data['Import'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateImport($data,$where) 
    {    
        if ($this->getImportByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Import' => $data['Import'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteImport($id){
    	if ($this->getImport($id)) {
    		return $this->delete($this->table,array('import_tire_port_id'=>$id));
    	}
    }
    public function getImport($id){
        return $this->getByID($this->table,$id);
    }
    public function getImportByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllImportByWhere($id){
        return $this->query('SELECT * FROM import_tire_port WHERE import_tire_port_id != '.$id);
    }
    public function getLastImport(){
        return $this->getLast($this->table);
    }
}
?>