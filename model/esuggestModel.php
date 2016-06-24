<?php

Class esuggestModel Extends baseModel {
	protected $table = "e_suggest";

	public function getAllSuggest($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createSuggest($data) 
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
    public function updateSuggest($data,$where) 
    {    
        if ($this->getSuggestByWhere($where)) {
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
    public function deleteSuggest($id){
    	if ($this->getSuggest($id)) {
    		return $this->delete($this->table,array('e_suggest_id'=>$id));
    	}
    }
    public function getSuggest($id){
        return $this->getByID($this->table,$id);
    }
    public function getSuggestByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllSuggestByWhere($id){
        return $this->query('SELECT * FROM e_suggest WHERE e_suggest_id != '.$id);
    }
    public function getLastSuggest(){
        return $this->getLast($this->table);
    }
}
?>