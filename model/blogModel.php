<?php

Class blogModel Extends baseModel {
	protected $table = "blog";

	public function get_blogs() 
    {
         return $this->fetchAll($this->table);
    }

    public function get_blog_detail($id) 
    {    
         return $this->getByID($this->table,$id);
    }
}
?>