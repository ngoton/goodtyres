<?php

Class logsModel Extends baseModel {
	protected $table = "logs";

	public function getAllLogs($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createLogs($data) 
    {    
        /*$data = array(
        	'Logs_id' => $data['Logs_id'],
        	'Logs_name' => $data['Logs_name'],
        	'Logs_birth' => $data['Logs_birth'],
        	'Logs_gender' => $data['Logs_gender'],
            'Logs_address' => $data['Logs_address'],
            'Logs_phone' => $data['Logs_phone'],
            'Logs_email' => $data['Logs_email'],
            'cmnd' => $data['cmnd'],
            'bank' => $data['bank'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateLogs($data,$where) 
    {    
        if ($this->getLogsByWhere($where)) {
        	/*$data = array(
            'Logs_id' => $data['Logs_id'],
            'Logs_name' => $data['Logs_name'],
            'Logs_birth' => $data['Logs_birth'],
            'Logs_gender' => $data['Logs_gender'],
            'Logs_address' => $data['Logs_address'],
            'Logs_phone' => $data['Logs_phone'],
            'Logs_email' => $data['Logs_email'],
            'cmnd' => $data['cmnd'],
            'bank' => $data['bank'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteLogs($id){
    	if ($this->getLogs($id)) {
    		return $this->delete($this->table,array('logs_id'=>$id));
    	}
    }
    public function getLogs($id){
        return $this->getByID($this->table,$id);
    }
    public function getLogsByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllLogsByWhere($id){
        return $this->query('SELECT * FROM logs WHERE logs_id != '.$id);
    }
    public function getLastLogs(){
        return $this->getLast($this->table);
    }
    public function getLogsByAccount($id){
        return $this->getByWhere($this->table,array('logs_user'=>$id));
    }
}
?>