<?php

Class fixedassetbuyModel Extends baseModel {
	protected $table = "fixed_asset_buy";

	public function getAllAsset($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createAsset($data) 
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
    public function updateAsset($data,$where) 
    {    
        if ($this->getAssetByWhere($where)) {
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
    public function deleteAsset($id){
    	if ($this->getAsset($id)) {
    		return $this->delete($this->table,array('fixed_asset_buy_id'=>$id));
    	}
    }
    public function getAsset($id){
        return $this->getByID($this->table,$id);
    }
    public function getAssetByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllAssetByWhere($id){
        return $this->query('SELECT * FROM fixed_asset_buy WHERE fixed_asset_buy_id != '.$id);
    }
    public function getLastAsset(){
        return $this->getLast($this->table);
    }
}
?>