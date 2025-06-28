<?php
require_once __DIR__ . '/_init.php';
$pdo = get_db_connection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Fetch all products or single product by id
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
            $stmt->execute([$_GET['id']]);
            $product = $stmt->fetch();
            if ($product) {
                send_json($product);
            } else {
                send_json(['error' => 'Product not found'], 404);
            }
        } else {
            $stmt = $pdo->query('SELECT * FROM products');
            $products = $stmt->fetchAll();
            send_json($products);
        }
        break;
    case 'POST':
        // Create product
        $data = json_decode(file_get_contents('php://input'), true);
        // Validate and insert (fields should match your DB)
        $stmt = $pdo->prepare('INSERT INTO products (name, description, price, sku) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $data['name'], $data['description'], $data['price'], $data['sku']
        ]);
        send_json(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;
    case 'PUT':
        // Update product
        parse_str(file_get_contents('php://input'), $data);
        $stmt = $pdo->prepare('UPDATE products SET name=?, description=?, price=?, sku=? WHERE id=?');
        $stmt->execute([
            $data['name'], $data['description'], $data['price'], $data['sku'], $data['id']
        ]);
        send_json(['success' => true]);
        break;
    case 'DELETE':
        // Delete product
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM products WHERE id=?');
            $stmt->execute([$id]);
            send_json(['success' => true]);
        } else {
            send_json(['error' => 'Product id required'], 400);
        }
        break;
    default:
        send_json(['error' => 'Method not allowed'], 405);
}
