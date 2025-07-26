<?php
// config/database.php

class Database {
    private $host = 'localhost';
    private $db_name = 'sohailpk_dairy_farm_management';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name}", 
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
        
        return $this->conn;
    }
}
?>