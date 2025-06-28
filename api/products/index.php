<?php
include_once '../config/cors.php';
include_once '../config/database.php';
include_once '../models/Product.php';

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['featured']) && $_GET['featured'] == 'true') {
            $stmt = $product->readFeatured();
        } else {
            $stmt = $product->read();
        }
        
        $products = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Get product images
            $imageQuery = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY position ASC";
            $imageStmt = $db->prepare($imageQuery);
            $imageStmt->bindParam(":product_id", $row['id']);
            $imageStmt->execute();
            $images = $imageStmt->fetchAll();

            // Get product specifications
            $specQuery = "SELECT * FROM product_specifications WHERE product_id = :product_id";
            $specStmt = $db->prepare($specQuery);
            $specStmt->bindParam(":product_id", $row['id']);
            $specStmt->execute();
            $specifications = $specStmt->fetchAll();

            $products[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'shortDescription' => $row['short_description'],
                'sku' => $row['sku'],
                'brand' => $row['brand'],
                'category' => [
                    'id' => $row['category_id'],
                    'name' => $row['category_name']
                ],
                'price' => floatval($row['price']),
                'compareAtPrice' => $row['compare_at_price'] ? floatval($row['compare_at_price']) : null,
                'images' => array_map(function($img) {
                    return [
                        'id' => $img['id'],
                        'url' => $img['url'],
                        'alt' => $img['alt'],
                        'position' => intval($img['position']),
                        'type' => $img['is_primary'] ? 'main' : 'gallery'
                    ];
                }, $images),
                'specifications' => array_map(function($spec) {
                    return [
                        'name' => $spec['name'],
                        'value' => $spec['value'],
                        'group' => $spec['spec_group']
                    ];
                }, $specifications),
                'inventory' => [
                    'quantity' => intval($row['inventory_quantity']),
                    'status' => $row['inventory_status'],
                    'lowStockThreshold' => intval($row['low_stock_threshold']),
                    'trackQuantity' => true
                ],
                'ratings' => [
                    'average' => 4.5, // Default rating
                    'count' => 0,
                    'distribution' => ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0]
                ],
                'tags' => $row['tags'] ? explode(',', $row['tags']) : [],
                'featured' => boolval($row['featured']),
                'status' => $row['status'],
                'seo' => [
                    'title' => $row['seo_title'],
                    'description' => $row['seo_description'],
                    'keywords' => $row['seo_keywords'] ? explode(',', $row['seo_keywords']) : [],
                    'slug' => $row['seo_slug']
                ],
                'createdAt' => $row['created_at'],
                'updatedAt' => $row['updated_at']
            ];
        }

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "data" => $products
        ]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->name) && !empty($data->sku) && !empty($data->price)) {
            $product->name = $data->name;
            $product->description = $data->description ?? '';
            $product->short_description = $data->shortDescription ?? '';
            $product->sku = $data->sku;
            $product->brand = $data->brand ?? '';
            $product->category_id = $data->categoryId ?? null;
            $product->price = $data->price;
            $product->compare_at_price = $data->compareAtPrice ?? null;
            $product->featured = $data->featured ?? false;
            $product->status = $data->status ?? 'active';
            $product->inventory_quantity = $data->inventory->quantity ?? 0;
            $product->inventory_status = $data->inventory->status ?? 'in_stock';
            $product->low_stock_threshold = $data->inventory->lowStockThreshold ?? 5;
            $product->seo_title = $data->seo->title ?? '';
            $product->seo_description = $data->seo->description ?? '';
            $product->seo_keywords = is_array($data->seo->keywords) ? implode(',', $data->seo->keywords) : '';
            $product->seo_slug = $data->seo->slug ?? '';
            $product->tags = is_array($data->tags) ? implode(',', $data->tags) : '';

            // Check if SKU exists
            if($product->skuExists()) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "SKU already exists"
                ]);
                break;
            }

            if($product->create()) {
                // Add images
                if(isset($data->images) && is_array($data->images)) {
                    foreach($data->images as $index => $image) {
                        $imageQuery = "INSERT INTO product_images 
                                      SET product_id=:product_id, url=:url, alt=:alt, 
                                          position=:position, is_primary=:is_primary";
                        $imageStmt = $db->prepare($imageQuery);
                        $imageStmt->bindParam(":product_id", $product->id);
                        $imageStmt->bindParam(":url", $image->url);
                        $imageStmt->bindParam(":alt", $image->alt);
                        $imageStmt->bindParam(":position", $image->position);
                        $imageStmt->bindParam(":is_primary", $image->type === 'main' ? 1 : 0);
                        $imageStmt->execute();
                    }
                }

                // Add specifications
                if(isset($data->specifications) && is_array($data->specifications)) {
                    foreach($data->specifications as $spec) {
                        $specQuery = "INSERT INTO product_specifications 
                                     SET product_id=:product_id, name=:name, value=:value, spec_group=:spec_group";
                        $specStmt = $db->prepare($specQuery);
                        $specStmt->bindParam(":product_id", $product->id);
                        $specStmt->bindParam(":name", $spec->name);
                        $specStmt->bindParam(":value", $spec->value);
                        $specStmt->bindParam(":spec_group", $spec->group);
                        $specStmt->execute();
                    }
                }

                http_response_code(201);
                echo json_encode([
                    "success" => true,
                    "message" => "Product created successfully",
                    "id" => $product->id
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Unable to create product"
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Required fields: name, sku, price"
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