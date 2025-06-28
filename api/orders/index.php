<?php
include_once '../config/cors.php';
include_once '../config/database.php';
include_once '../models/Order.php';

$database = new Database();
$db = $database->getConnection();
$order = new Order($db);

$method = $_SERVER['REQUEST_METHOD'];

// Get user ID from token
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
        if(isset($_GET['analytics'])) {
            // Admin analytics
            $analytics = $order->getAnalytics();
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "data" => $analytics
            ]);
        } else if(isset($_GET['admin'])) {
            // Admin view - all orders
            $stmt = $order->read();
            $orders = [];
            
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $orders[] = [
                    'id' => $row['id'],
                    'orderNumber' => $row['order_number'],
                    'userId' => $row['user_id'],
                    'customer' => [
                        'firstName' => $row['first_name'],
                        'lastName' => $row['last_name'],
                        'email' => $row['email']
                    ],
                    'status' => $row['status'],
                    'paymentStatus' => $row['payment_status'],
                    'paymentMethod' => $row['payment_method'],
                    'totals' => [
                        'subtotal' => floatval($row['subtotal']),
                        'tax' => floatval($row['tax']),
                        'shipping' => floatval($row['shipping']),
                        'discount' => floatval($row['discount']),
                        'total' => floatval($row['total']),
                        'currency' => $row['currency']
                    ],
                    'createdAt' => $row['created_at'],
                    'updatedAt' => $row['updated_at']
                ];
            }

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "data" => $orders
            ]);
        } else {
            // User orders
            if(!$user_id) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Unauthorized"]);
                break;
            }

            $stmt = $order->readByUser($user_id);
            $orders = [];
            
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $orders[] = [
                    'id' => $row['id'],
                    'orderNumber' => $row['order_number'],
                    'status' => $row['status'],
                    'paymentStatus' => $row['payment_status'],
                    'paymentMethod' => $row['payment_method'],
                    'totals' => [
                        'subtotal' => floatval($row['subtotal']),
                        'tax' => floatval($row['tax']),
                        'shipping' => floatval($row['shipping']),
                        'discount' => floatval($row['discount']),
                        'total' => floatval($row['total']),
                        'currency' => $row['currency']
                    ],
                    'billingAddress' => json_decode($row['billing_address'], true),
                    'shippingAddress' => json_decode($row['shipping_address'], true),
                    'createdAt' => $row['created_at'],
                    'updatedAt' => $row['updated_at']
                ];
            }

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "data" => $orders
            ]);
        }
        break;

    case 'POST':
        if(!$user_id) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Unauthorized"]);
            break;
        }

        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->items) && !empty($data->billingAddress) && !empty($data->total)) {
            $order->user_id = $user_id;
            $order->status = 'pending';
            $order->subtotal = $data->subtotal;
            $order->tax = $data->tax;
            $order->shipping = $data->shipping;
            $order->discount = $data->discount ?? 0;
            $order->total = $data->total;
            $order->currency = $data->currency ?? 'USD';
            $order->payment_status = 'pending';
            $order->payment_method = $data->paymentMethod ?? 'card';
            $order->billing_address = $data->billingAddress;
            $order->shipping_address = $data->shippingAddress ?? $data->billingAddress;
            $order->notes = $data->notes ?? '';

            if($order->create()) {
                // Add order items
                foreach($data->items as $item) {
                    $itemQuery = "INSERT INTO order_items 
                                 SET order_id=:order_id, product_id=:product_id, variant_id=:variant_id,
                                     quantity=:quantity, price=:price, total=:total";
                    $itemStmt = $db->prepare($itemQuery);
                    $itemStmt->bindParam(":order_id", $order->id);
                    $itemStmt->bindParam(":product_id", $item->productId);
                    $itemStmt->bindParam(":variant_id", $item->variantId);
                    $itemStmt->bindParam(":quantity", $item->quantity);
                    $itemStmt->bindParam(":price", $item->price);
                    $total = $item->price * $item->quantity;
                    $itemStmt->bindParam(":total", $total);
                    $itemStmt->execute();
                }

                // Clear user's cart
                $cartQuery = "DELETE FROM cart_items WHERE user_id = :user_id";
                $cartStmt = $db->prepare($cartQuery);
                $cartStmt->bindParam(":user_id", $user_id);
                $cartStmt->execute();

                http_response_code(201);
                echo json_encode([
                    "success" => true,
                    "message" => "Order created successfully",
                    "orderId" => $order->id,
                    "orderNumber" => $order->order_number
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Unable to create order"
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Required fields: items, billingAddress, total"
            ]);
        }
        break;

    case 'PUT':
        // Update order status (admin only)
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->orderId) && !empty($data->status)) {
            if($order->updateStatus($data->orderId, $data->status)) {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => "Order status updated"
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Unable to update order status"
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Required fields: orderId, status"
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