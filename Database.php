<?php
/**
 * Created by PhpStorm.
 * User: sange
 * Date: 11/25/14
 * Time: 1:31 AM
 */

class Database {
    private $serverName = "localhost";
    private $db = "co-sc";
    private $mysqli;

    public function __construct(){

    }

    public function create_connection($username, $password){
        return $this->mysqli = new mysqli($this->serveName, $username, $password, $this->db);
    }

    public function close_connection($username, $password){
        $this->mysqli->close();
    }

} 