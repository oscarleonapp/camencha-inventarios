<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'inventario_sistema';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8mb4");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            // Log error securely instead of exposing to user
            error_log("Database connection error: " . $exception->getMessage());
            die("Error de conexión a la base de datos. Por favor contacte al administrador.");
        }

        return $this->conn;
    }
}
?>