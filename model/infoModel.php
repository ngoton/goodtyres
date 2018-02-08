<?php

Class infoModel Extends baseModel {
	protected $table = "info";

	public function getAllInfo($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createInfo($data) 
    {    
        /*$data = array(
        	'Info_id' => $data['Info_id'],
        	'Info_name' => $data['Info_name'],
        	'Info_birth' => $data['Info_birth'],
        	'Info_gender' => $data['Info_gender'],
            'Info_address' => $data['Info_address'],
            'Info_phone' => $data['Info_phone'],
            'Info_email' => $data['Info_email'],
            'cmnd' => $data['cmnd'],
            'bank' => $data['bank'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateInfo($data,$where) 
    {    
        if ($this->getInfoByWhere($where)) {
        	/*$data = array(
            'Info_id' => $data['Info_id'],
            'Info_name' => $data['Info_name'],
            'Info_birth' => $data['Info_birth'],
            'Info_gender' => $data['Info_gender'],
            'Info_address' => $data['Info_address'],
            'Info_phone' => $data['Info_phone'],
            'Info_email' => $data['Info_email'],
            'cmnd' => $data['cmnd'],
            'bank' => $data['bank'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteInfo($id){
    	if ($this->getInfo($id)) {
    		return $this->delete($this->table,array('info_id'=>$id));
    	}
    }
    public function getInfo($id){
        return $this->getByID($this->table,$id);
    }
    public function getInfoByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllInfoByWhere($id){
        return $this->query('SELECT * FROM info WHERE info_id != '.$id);
    }
    public function getLastInfo(){
        return $this->getLast($this->table);
    }
    public function queryInfo($sql){
        return $this->query($sql);
    }
}
?>