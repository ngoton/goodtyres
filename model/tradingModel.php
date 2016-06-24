<?php

Class tradingModel Extends baseModel {
	protected $table = "trading";

	public function getAllTrading($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createTrading($data) 
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
            'Trading' => $data['Trading'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateTrading($data,$where) 
    {    
        if ($this->getTradingByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Trading' => $data['Trading'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteTrading($id){
    	if ($this->getTrading($id)) {
    		return $this->delete($this->table,array('trading_id'=>$id));
    	}
    }
    public function getTrading($id){
        return $this->getByID($this->table,$id);
    }
    public function getTradingByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllTradingByWhere($id){
        return $this->query('SELECT * FROM trading WHERE trading_id != '.$id);
    }
    public function getLastTrading(){
        return $this->getLast($this->table);
    }
    public function queryTrading($sql){
        return $this->query($sql);
    }
}
?>