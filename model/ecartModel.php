<?php

Class ecartModel Extends baseModel {
	protected $table = "e_cart";

	public function getAllCart($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createCart($data) 
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
    public function updateCart($data,$where) 
    {    
        if ($this->getCartByWhere($where)) {
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
    public function deleteCart($id){
    	if ($this->getCart($id)) {
    		return $this->delete($this->table,array('e_cart_id'=>$id));
    	}
    }
    public function getCart($id){
        return $this->getByID($this->table,$id);
    }
    public function getCartByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllCartByWhere($id){
        return $this->query('SELECT * FROM e_cart WHERE e_cart_id != '.$id);
    }
    public function getLastCart(){
        return $this->getLast($this->table);
    }
    public function queryCart($sql){
        return $this->query($sql);
    }
}
?>