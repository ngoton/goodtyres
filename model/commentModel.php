<?php

Class commentModel Extends baseModel {
	protected $table = "comment";

	public function getAllComment($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createComment($data) 
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
            'Comment' => $data['Comment'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateComment($data,$where) 
    {    
        if ($this->getCommentByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Comment' => $data['Comment'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteComment($id){
    	if ($this->getComment($id)) {
    		return $this->delete($this->table,array('comment_id'=>$id));
    	}
    }
    public function getComment($id){
        return $this->getByID($this->table,$id);
    }
    public function getCommentByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllCommentByWhere($id){
        return $this->query('SELECT * FROM comment WHERE comment_id != '.$id);
    }
    public function getLastComment(){
        return $this->getLast($this->table);
    }
    public function queryComment($sql){
        return $this->query($sql);
    }
}
?>