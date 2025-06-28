<?php
// Autoload and common functions for API
session_start();

function get_db_connection() {
    $config = require __DIR__ . '/../.env.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    try {
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed.']);
        exit;
    }
}

function send_json($data, $code = 200) {
    // Set CORS headers
    $allowedOrigins = ['http://localhost:5173', 'https://www.autriq.com', 'https://autriq.com'];
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    header('Content-Type: application/json');
    header('Vary: Origin');

    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        header('Access-Control-Max-Age: 86400'); // 24 hours
        exit(0);
    }
    
    // Send the actual response
    http_response_code($code);
    
    if (!empty($data)) {
        echo json_encode($data);
    }
    
    exit;
}
