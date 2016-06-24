<?php

Class portModel Extends baseModel {
    protected $table = "port";

    public function getAllPort($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createPort($data) 
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
    public function updatePort($data,$where) 
    {    
        if ($this->getPortByWhere($where)) {
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
    public function deletePort($id){
        if ($this->getPort($id)) {
            return $this->delete($this->table,array('port_id'=>$id));
        }
    }
    public function getPort($id){
        return $this->getByID($this->table,$id);
    }
    public function getPortByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getAllPortByWhere($id){
        return $this->query('SELECT * FROM port WHERE port_id != '.$id);
    }
    public function getLastPort(){
        return $this->getLast($this->table);
    }
    public function checkPort($id,$port_name,$district){
        return $this->query('SELECT * FROM port WHERE port_id != '.$id.' AND port_name = "'.$port_name.'" AND district = '.$district);
    }
}
?>