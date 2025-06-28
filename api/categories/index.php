<?php
include_once '../config/cors.php';
include_once '../config/database.php';
include_once '../models/Category.php';

$database = new Database();
$db = $database->getConnection();
$category = new Category($db);

$method = $_SERVER['REQUEST_METHOD'];

// Log the request for debugging
error_log("Categories API called with method: " . $method);

switch($method) {
    case 'GET':
        try {
            if(isset($_GET['featured']) && $_GET['featured'] == 'true') {
                $stmt = $category->readFeatured();
            } else {
                $stmt = $category->read();
            }
            
            $categories = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categories[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'slug' => $row['slug'],
                    'description' => $row['description'],
                    'image' => $row['image'],
                    'parentId' => $row['parent_id'],
                    'productCount' => intval($row['product_count']),
                    'featured' => boolval($row['featured']),
                    'seo' => [
                        'title' => $row['seo_title'],
                        'description' => $row['seo_description'],
                        'keywords' => $row['seo_keywords'] ? explode(',', $row['seo_keywords']) : [],
                        'slug' => $row['slug']
                    ],
                    'createdAt' => $row['created_at'],
                    'updatedAt' => $row['updated_at']
                ];
            }

            error_log("Categories found: " . count($categories));

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "data" => $categories,
                "count" => count($categories)
            ]);
        } catch (Exception $e) {
            error_log("Categories API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->name) && !empty($data->slug)) {
            $category->name = $data->name;
            $category->slug = $data->slug;
            $category->description = $data->description ?? '';
            $category->image = $data->image ?? '';
            $category->parent_id = $data->parentId ?? null;
            $category->featured = $data->featured ?? false;
            $category->seo_title = $data->seo->title ?? '';
            $category->seo_description = $data->seo->description ?? '';
            $category->seo_keywords = is_array($data->seo->keywords) ? implode(',', $data->seo->keywords) : '';

            if($category->create()) {
                http_response_code(201);
                echo json_encode([
                    "success" => true,
                    "message" => "Category created successfully",
                    "id" => $category->id
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Unable to create category"
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Required fields: name, slug"
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Method not allowed"
        ]);
        break;
}
?>