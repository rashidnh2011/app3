<?php
class Order {
    private $conn;
    private $table_name = "orders";

    public $id;
    public $order_number;
    public $user_id;
    public $status;
    public $subtotal;
    public $tax;
    public $shipping;
    public $discount;
    public $total;
    public $currency;
    public $payment_status;
    public $payment_method;
    public $billing_address;
    public $shipping_address;
    public $notes;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET order_number=:order_number, user_id=:user_id, status=:status,
                      subtotal=:subtotal, tax=:tax, shipping=:shipping, discount=:discount,
                      total=:total, currency=:currency, payment_status=:payment_status,
                      payment_method=:payment_method, billing_address=:billing_address,
                      shipping_address=:shipping_address, notes=:notes";

        $stmt = $this->conn->prepare($query);

        $this->order_number = $this->generateOrderNumber();
        $this->billing_address = json_encode($this->billing_address);
        $this->shipping_address = json_encode($this->shipping_address);
        $this->notes = htmlspecialchars(strip_tags($this->notes));

        $stmt->bindParam(":order_number", $this->order_number);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":subtotal", $this->subtotal);
        $stmt->bindParam(":tax", $this->tax);
        $stmt->bindParam(":shipping", $this->shipping);
        $stmt->bindParam(":discount", $this->discount);
        $stmt->bindParam(":total", $this->total);
        $stmt->bindParam(":currency", $this->currency);
        $stmt->bindParam(":payment_status", $this->payment_status);
        $stmt->bindParam(":payment_method", $this->payment_method);
        $stmt->bindParam(":billing_address", $this->billing_address);
        $stmt->bindParam(":shipping_address", $this->shipping_address);
        $stmt->bindParam(":notes", $this->notes);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function read() {
        $query = "SELECT o.*, u.first_name, u.last_name, u.email
                  FROM " . $this->table_name . " o
                  LEFT JOIN users u ON o.user_id = u.id
                  ORDER BY o.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readByUser($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt;
    }

    public function updateStatus($order_id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status, updated_at = NOW() 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $order_id);
        return $stmt->execute();
    }

    private function generateOrderNumber() {
        return 'AUT-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function getAnalytics() {
        $analytics = [];
        
        // Total orders
        $query = "SELECT COUNT(*) as total_orders FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $analytics['total_orders'] = $stmt->fetch()['total_orders'];

        // Total revenue
        $query = "SELECT SUM(total) as total_revenue FROM " . $this->table_name . " WHERE status != 'cancelled'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $analytics['total_revenue'] = $stmt->fetch()['total_revenue'] ?: 0;

        // Average order value
        $query = "SELECT AVG(total) as avg_order_value FROM " . $this->table_name . " WHERE status != 'cancelled'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $analytics['avg_order_value'] = $stmt->fetch()['avg_order_value'] ?: 0;

        // Orders by status
        $query = "SELECT status, COUNT(*) as count FROM " . $this->table_name . " GROUP BY status";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $analytics['orders_by_status'] = $stmt->fetchAll();

        // Monthly revenue
        $query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total) as revenue 
                  FROM " . $this->table_name . " 
                  WHERE status != 'cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                  GROUP BY month 
                  ORDER BY month";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $analytics['monthly_revenue'] = $stmt->fetchAll();

        return $analytics;
    }
}
?>