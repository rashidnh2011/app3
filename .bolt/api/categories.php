<?php
require_once __DIR__ . '/_init.php';
$pdo = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch all categories
    $stmt = $pdo->query('SELECT * FROM categories');
    $categories = $stmt->fetchAll();
    send_json($categories);
} else {
    send_json(['error' => 'Method not allowed'], 405);
}
