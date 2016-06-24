<?php

Class assetsModel Extends baseModel {
	protected $table = "assets";

	public function getAllAssets($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createAssets($data) 
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
            'Assets' => $data['Assets'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateAssets($data,$where) 
    {    
        if ($this->getAssetsByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Assets' => $data['Assets'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteAssets($id){
    	if ($this->getAssets($id)) {
    		return $this->delete($this->table,array('assets_id'=>$id));
    	}
    }
    public function getAssets($id){
        return $this->getByID($this->table,$id);
    }
    public function getAssetsByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllAssetsByWhere($id){
        return $this->query('SELECT * FROM assets WHERE assets_id != '.$id);
    }
    public function getLastAssets(){
        return $this->getLast($this->table);
    }
    public function queryAssets($sql){
        return $this->query($sql);
    }
}
?>