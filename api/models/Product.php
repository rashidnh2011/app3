<?php
class Product {
    private $conn;
    private $table_name = "products";

    public $id;
    public $name;
    public $description;
    public $short_description;
    public $sku;
    public $brand;
    public $category_id;
    public $price;
    public $compare_at_price;
    public $featured;
    public $status;
    public $inventory_quantity;
    public $inventory_status;
    public $low_stock_threshold;
    public $seo_title;
    public $seo_description;
    public $seo_keywords;
    public $seo_slug;
    public $tags;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, description=:description, short_description=:short_description,
                      sku=:sku, brand=:brand, category_id=:category_id, price=:price,
                      compare_at_price=:compare_at_price, featured=:featured, status=:status,
                      inventory_quantity=:inventory_quantity, inventory_status=:inventory_status,
                      low_stock_threshold=:low_stock_threshold, seo_title=:seo_title,
                      seo_description=:seo_description, seo_keywords=:seo_keywords,
                      seo_slug=:seo_slug, tags=:tags";

        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->short_description = htmlspecialchars(strip_tags($this->short_description));
        $this->sku = htmlspecialchars(strip_tags($this->sku));
        $this->brand = htmlspecialchars(strip_tags($this->brand));
        $this->seo_title = htmlspecialchars(strip_tags($this->seo_title));
        $this->seo_description = htmlspecialchars(strip_tags($this->seo_description));
        $this->seo_keywords = htmlspecialchars(strip_tags($this->seo_keywords));
        $this->seo_slug = htmlspecialchars(strip_tags($this->seo_slug));
        $this->tags = htmlspecialchars(strip_tags($this->tags));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":short_description", $this->short_description);
        $stmt->bindParam(":sku", $this->sku);
        $stmt->bindParam(":brand", $this->brand);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":compare_at_price", $this->compare_at_price);
        $stmt->bindParam(":featured", $this->featured);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":inventory_quantity", $this->inventory_quantity);
        $stmt->bindParam(":inventory_status", $this->inventory_status);
        $stmt->bindParam(":low_stock_threshold", $this->low_stock_threshold);
        $stmt->bindParam(":seo_title", $this->seo_title);
        $stmt->bindParam(":seo_description", $this->seo_description);
        $stmt->bindParam(":seo_keywords", $this->seo_keywords);
        $stmt->bindParam(":seo_slug", $this->seo_slug);
        $stmt->bindParam(":tags", $this->tags);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function read() {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.status = 'active'
                  ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readAll() {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readFeatured() {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.featured = 1 AND p.status = 'active'
                  ORDER BY p.created_at DESC
                  LIMIT 6";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.id = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->short_description = $row['short_description'];
            $this->sku = $row['sku'];
            $this->brand = $row['brand'];
            $this->category_id = $row['category_id'];
            $this->price = $row['price'];
            $this->compare_at_price = $row['compare_at_price'];
            $this->featured = $row['featured'];
            $this->status = $row['status'];
            $this->inventory_quantity = $row['inventory_quantity'];
            $this->inventory_status = $row['inventory_status'];
            $this->low_stock_threshold = $row['low_stock_threshold'];
            $this->seo_title = $row['seo_title'];
            $this->seo_description = $row['seo_description'];
            $this->seo_keywords = $row['seo_keywords'];
            $this->seo_slug = $row['seo_slug'];
            $this->tags = $row['tags'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name=:name, description=:description, short_description=:short_description,
                      sku=:sku, brand=:brand, category_id=:category_id, price=:price,
                      compare_at_price=:compare_at_price, featured=:featured, status=:status,
                      inventory_quantity=:inventory_quantity, inventory_status=:inventory_status,
                      low_stock_threshold=:low_stock_threshold, seo_title=:seo_title,
                      seo_description=:seo_description, seo_keywords=:seo_keywords,
                      seo_slug=:seo_slug, tags=:tags, updated_at=NOW()
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->short_description = htmlspecialchars(strip_tags($this->short_description));
        $this->sku = htmlspecialchars(strip_tags($this->sku));
        $this->brand = htmlspecialchars(strip_tags($this->brand));
        $this->seo_title = htmlspecialchars(strip_tags($this->seo_title));
        $this->seo_description = htmlspecialchars(strip_tags($this->seo_description));
        $this->seo_keywords = htmlspecialchars(strip_tags($this->seo_keywords));
        $this->seo_slug = htmlspecialchars(strip_tags($this->seo_slug));
        $this->tags = htmlspecialchars(strip_tags($this->tags));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":short_description", $this->short_description);
        $stmt->bindParam(":sku", $this->sku);
        $stmt->bindParam(":brand", $this->brand);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":compare_at_price", $this->compare_at_price);
        $stmt->bindParam(":featured", $this->featured);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":inventory_quantity", $this->inventory_quantity);
        $stmt->bindParam(":inventory_status", $this->inventory_status);
        $stmt->bindParam(":low_stock_threshold", $this->low_stock_threshold);
        $stmt->bindParam(":seo_title", $this->seo_title);
        $stmt->bindParam(":seo_description", $this->seo_description);
        $stmt->bindParam(":seo_keywords", $this->seo_keywords);
        $stmt->bindParam(":seo_slug", $this->seo_slug);
        $stmt->bindParam(":tags", $this->tags);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }

    public function skuExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE sku = :sku";
        if($this->id) {
            $query .= " AND id != :id";
        }
        $query .= " LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":sku", $this->sku);
        if($this->id) {
            $stmt->bindParam(":id", $this->id);
        }
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>