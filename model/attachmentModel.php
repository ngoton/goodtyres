<?php

Class attachmentModel Extends baseModel {
	protected $table = "attachment";

	public function getAllAttachment($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createAttachment($data) 
    {    
        
        return $this->insert($this->table,$data);
    }
    public function updateAttachment($data,$id) 
    {    
        if ($this->getAttachmentByWhere($id)) {
        	
	        return $this->update($this->table,$data,$id);
        }
        
    }
    public function deleteAttachment($id){
    	if ($this->getAttachment($id)) {
    		return $this->delete($this->table,array('attachment_id'=>$id));
    	}
    }
    public function getAttachment($id){
    	return $this->getByID($this->table,$id);
    }
    public function getAttachmentByWhere($where){
        return $this->getByWhere($this->table,$where);
    }
    public function getLastAttachment(){
        return $this->getLast($this->table);
    }
    public function queryAttachment($sql){
        return $this->query($sql);
    }
}
?>