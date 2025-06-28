<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $email;
    public $password;
    public $first_name;
    public $last_name;
    public $phone;
    public $avatar;
    public $google_id;
    public $loyalty_points;
    public $membership_tier;
    public $email_verified;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET email=:email, password=:password, first_name=:first_name, 
                      last_name=:last_name, phone=:phone, avatar=:avatar, 
                      google_id=:google_id, loyalty_points=:loyalty_points, 
                      membership_tier=:membership_tier, email_verified=:email_verified";

        $stmt = $this->conn->prepare($query);

        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->avatar = htmlspecialchars(strip_tags($this->avatar));
        $this->google_id = htmlspecialchars(strip_tags($this->google_id));
        $this->loyalty_points = $this->loyalty_points ?: 100;
        $this->membership_tier = $this->membership_tier ?: 'bronze';
        $this->email_verified = $this->email_verified ?: 0;

        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":avatar", $this->avatar);
        $stmt->bindParam(":google_id", $this->google_id);
        $stmt->bindParam(":loyalty_points", $this->loyalty_points);
        $stmt->bindParam(":membership_tier", $this->membership_tier);
        $stmt->bindParam(":email_verified", $this->email_verified);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function login($email, $password) {
        $query = "SELECT id, email, password, first_name, last_name, phone, avatar, 
                         loyalty_points, membership_tier, email_verified, created_at 
                  FROM " . $this->table_name . " 
                  WHERE email = :email LIMIT 1";

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
                $this->phone = $row['phone'];
                $this->avatar = $row['avatar'];
                $this->loyalty_points = $row['loyalty_points'];
                $this->membership_tier = $row['membership_tier'];
                $this->email_verified = $row['email_verified'];
                $this->created_at = $row['created_at'];
                
                // Update last login
                $this->updateLastLogin();
                return true;
            }
        }
        return false;
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByGoogleId($google_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE google_id = :google_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":google_id", $google_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function updateLastLogin() {
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
    }

    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>