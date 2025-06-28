<?php
include_once '../config/cors.php';
include_once '../config/database.php';
include_once '../models/Cart.php';

$database = new Database();
$db = $database->getConnection();
$cart = new Cart($db);

$method = $_SERVER['REQUEST_METHOD'];

// Get user ID from token (simplified for demo)
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
$user_id = null;

if($token) {
    $decoded = json_decode(base64_decode($token), true);
    if($decoded && isset($decoded['id']) && $decoded['exp'] > time()) {
        $user_id = $decoded['id'];
    }
}

switch($method) {
    case 'GET':
        if(!$user_id) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Unauthorized"]);
            break;
        }

        $items = $cart->getCartItems($user_id);
        
        $subtotal = 0;
        $cartItems = [];
        
        foreach($items as $item) {
            $itemTotal = $item['price'] * $item['quantity'];
            $subtotal += $itemTotal;
            
            $cartItems[] = [
                'id' => $item['id'],
                'productId' => $item['product_id'],
                'variantId' => $item['variant_id'],
                'quantity' => intval($item['quantity']),
                'price' => floatval($item['price']),
                'product' => [
                    'id' => $item['product_id'],
                    'name' => $item['name'],
                    'sku' => $item['sku'],
                    'brand' => $item['brand'],
                    'images' => [
                        [
                            'url' => $item['image_url'],
                            'alt' => $item['name']
                        ]
                    ]
                ],
                'addedAt' => $item['created_at']
            ];
        }

        $tax = $subtotal * 0.08; // 8% tax
        $shipping = $subtotal > 100 ? 0 : 15; // Free shipping over $100
        $total = $subtotal + $tax + $shipping;

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "data" => [
                'id' => 'cart-' . $user_id,
                'items' => $cartItems,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'discount' => 0,
                'total' => $total,
                'currency' => 'USD',
                'updatedAt' => date('c')
            ]
        ]);
        break;

    case 'POST':
        if(!$user_id) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Unauthorized"]);
            break;
        }

        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->productId) && !empty($data->price)) {
            $cart->user_id = $user_id;
            $cart->product_id = $data->productId;
            $cart->variant_id = $data->variantId ?? null;
            $cart->quantity = $data->quantity ?? 1;
            $cart->price = $data->price;

            if($cart->addItem()) {
                http_response_code(201);
                echo json_encode([
                    "success" => true,
                    "message" => "Item added to cart"
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Unable to add item to cart"
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Required fields: productId, price"
            ]);
        }
        break;

    case 'PUT':
        if(!$user_id) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Unauthorized"]);
            break;
        }

        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id) && isset($data->quantity)) {
            $cart->id = $data->id;
            $cart->quantity = $data->quantity;

            if($cart->updateQuantity()) {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => "Cart item updated"
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Unable to update cart item"
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Required fields: id, quantity"
            ]);
        }
        break;

    case 'DELETE':
        if(!$user_id) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Unauthorized"]);
            break;
        }

        if(isset($_GET['id'])) {
            $cart->id = $_GET['id'];
            if($cart->removeItem()) {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => "Item removed from cart"
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Unable to remove item"
                ]);
            }
        } else if(isset($_GET['clear'])) {
            if($cart->clearCart($user_id)) {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => "Cart cleared"
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Unable to clear cart"
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Item ID required"
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