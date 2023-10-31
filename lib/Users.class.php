<?php

class Users
{
	private $db;

	public function __construct($db) {
        $this->db = $db;
    }

	public function GetUserId($token){
		$sql  = 'SELECT id FROM ' . _DB_TABLE_USER_ . ' WHERE token=?s';
    	$id = $this->db->getOne($sql, $token);

		return $id;
	}


}
