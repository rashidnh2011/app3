<?php
class Category {
    private $conn;
    private $table_name = "categories";

    public $id;
    public $name;
    public $slug;
    public $description;
    public $image;
    public $parent_id;
    public $featured;
    public $seo_title;
    public $seo_description;
    public $seo_keywords;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT c.*, 
                         (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active') as product_count
                  FROM " . $this->table_name . " c
                  ORDER BY c.name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readFeatured() {
        $query = "SELECT c.*, 
                         (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active') as product_count
                  FROM " . $this->table_name . " c
                  WHERE c.featured = 1
                  ORDER BY c.name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, slug=:slug, description=:description, image=:image,
                      parent_id=:parent_id, featured=:featured, seo_title=:seo_title,
                      seo_description=:seo_description, seo_keywords=:seo_keywords";

        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->slug = htmlspecialchars(strip_tags($this->slug));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->image = htmlspecialchars(strip_tags($this->image));
        $this->seo_title = htmlspecialchars(strip_tags($this->seo_title));
        $this->seo_description = htmlspecialchars(strip_tags($this->seo_description));
        $this->seo_keywords = htmlspecialchars(strip_tags($this->seo_keywords));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":slug", $this->slug);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":image", $this->image);
        $stmt->bindParam(":parent_id", $this->parent_id);
        $stmt->bindParam(":featured", $this->featured);
        $stmt->bindParam(":seo_title", $this->seo_title);
        $stmt->bindParam(":seo_description", $this->seo_description);
        $stmt->bindParam(":seo_keywords", $this->seo_keywords);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
}
?>