<?php
class Cart {
    private $conn;
    private $table_name = "cart_items";

    public $id;
    public $user_id;
    public $product_id;
    public $variant_id;
    public $quantity;
    public $price;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function addItem() {
        // Check if item already exists
        $existing = $this->getExistingItem();
        if($existing) {
            // Update quantity
            $this->id = $existing['id'];
            $this->quantity = $existing['quantity'] + $this->quantity;
            return $this->updateQuantity();
        } else {
            // Add new item
            $query = "INSERT INTO " . $this->table_name . " 
                      SET user_id=:user_id, product_id=:product_id, variant_id=:variant_id,
                          quantity=:quantity, price=:price";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":product_id", $this->product_id);
            $stmt->bindParam(":variant_id", $this->variant_id);
            $stmt->bindParam(":quantity", $this->quantity);
            $stmt->bindParam(":price", $this->price);

            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            return false;
        }
    }

    public function getCartItems($user_id) {
        $query = "SELECT ci.*, p.name, p.sku, p.brand, pi.url as image_url
                  FROM " . $this->table_name . " ci
                  LEFT JOIN products p ON ci.product_id = p.id
                  LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                  WHERE ci.user_id = :user_id
                  ORDER BY ci.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateQuantity() {
        $query = "UPDATE " . $this->table_name . " 
                  SET quantity = :quantity, updated_at = NOW() 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }

    public function removeItem() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }

    public function clearCart($user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        return $stmt->execute();
    }

    private function getExistingItem() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND product_id = :product_id";
        
        if($this->variant_id) {
            $query .= " AND variant_id = :variant_id";
        } else {
            $query .= " AND variant_id IS NULL";
        }
        
        $query .= " LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":product_id", $this->product_id);
        if($this->variant_id) {
            $stmt->bindParam(":variant_id", $this->variant_id);
        }
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>