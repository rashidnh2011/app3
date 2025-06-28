<?php
class Database {
    // Production database configuration for Hostinger
    private $host = "localhost";
    private $db_name = "u992617610_autriq";
    private $username = "u992617610_autriq";
    private $password = "Autriq@7343";
    private $charset = "utf8mb4";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_PERSISTENT => false, // Disable for shared hosting
                PDO::ATTR_TIMEOUT => 30,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Set timezone
            $this->conn->exec("SET time_zone = '+00:00'");
            
        } catch(PDOException $exception) {
            // Log error securely in production
            error_log("Database connection error: " . $exception->getMessage());
            
            // Return a more detailed error for debugging
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Database connection failed",
                "error" => $exception->getMessage(),
                "host" => $this->host,
                "database" => $this->db_name
            ]);
            exit();
        }
        return $this->conn;
    }
    
    // Health check method
    public function isConnected() {
        try {
            return $this->conn && $this->conn->query('SELECT 1')->fetchColumn() === '1';
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>