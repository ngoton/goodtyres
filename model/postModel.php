<?php

Class postModel Extends baseModel {
	protected $table = "post";

	public function getAllPost($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createPost($data) 
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
    public function updatePost($data,$where) 
    {    
        if ($this->getPostByWhere($where)) {
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
    public function deletePost($id){
    	if ($this->getPost($id)) {
    		return $this->delete($this->table,array('post_id'=>$id));
    	}
    }
    public function getPost($id){
        return $this->getByID($this->table,$id);
    }
    public function getPostByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllPostByWhere($id){
        return $this->query('SELECT * FROM post WHERE post_id != '.$id);
    }
    public function queryPost($sql){
        return $this->query($sql);
    }
    public function getLastPost(){
        return $this->getLast($this->table);
    }
}
?>