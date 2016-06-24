<?php

Class othercostModel Extends baseModel {
	protected $table = "other_cost";

	public function getAllOthercost($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createOthercost($data) 
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
            'Othercost' => $data['Othercost'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateOthercost($data,$where) 
    {    
        if ($this->getOthercostByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Othercost' => $data['Othercost'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteOthercost($id){
    	if ($this->getOthercost($id)) {
    		return $this->delete($this->table,array('other_cost_id'=>$id));
    	}
    }
    public function deleteOthercost2($id){
        
            return $this->delete($this->table,array('sale_report'=>$id));
        
    }
    public function deleteOthercost3($id){
        
            return $this->delete($this->table,array('trading'=>$id));
        
    }
    public function getOthercost($id){
        return $this->getByID($this->table,$id);
    }
    public function getOthercostByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllOthercostByWhere($id){
        return $this->query('SELECT * FROM other_cost WHERE other_cost_id != '.$id);
    }
    public function getLastOthercost(){
        return $this->getLast($this->table);
    }
    public function queryOthercost($sql){
        return $this->query($sql);
    }
}
?>