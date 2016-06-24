<?php

Class agentModel Extends baseModel {
	protected $table = "agent";

	public function getAllAgent($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createAgent($data) 
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
            'Agent' => $data['Agent'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateAgent($data,$where) 
    {    
        if ($this->getAgentByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Agent' => $data['Agent'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteAgent($id){
    	if ($this->getAgent($id)) {
    		return $this->delete($this->table,array('agent_id'=>$id));
    	}
    }
    public function getAgent($id){
        return $this->getByID($this->table,$id);
    }
    public function getAgentByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllAgentByWhere($id){
        return $this->query('SELECT * FROM agent WHERE agent_id != '.$id);
    }
    public function getLastAgent(){
        return $this->getLast($this->table);
    }
    public function queryAgent($sql){
        return $this->query($sql);
    }
}
?>