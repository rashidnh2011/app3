<?php
class Admin {
    private $conn;
    private $table_name = "admins";

    public $id;
    public $email;
    public $password;
    public $first_name;
    public $last_name;
    public $role;
    public $permissions;
    public $avatar;
    public $created_at;
    public $last_login;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($email, $password) {
        $query = "SELECT id, email, password, first_name, last_name, role, 
                         permissions, avatar, created_at 
                  FROM " . $this->table_name . " 
                  WHERE email = :email AND active = 1 LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->email = $row['email'];
                $this->first_name = $row['first_name'];
                $this->last_name = $row['last_name'];
                $this->role = $row['role'];
                $this->permissions = json_decode($row['permissions'], true);
                $this->avatar = $row['avatar'];
                $this->created_at = $row['created_at'];
                
                // Update last login
                $this->updateLastLogin();
                return true;
            }
        }
        return false;
    }

    private function updateLastLogin() {
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
    }

    public function hasPermission($permission) {
        return in_array($permission, $this->permissions);
    }
}
?>