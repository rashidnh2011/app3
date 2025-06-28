<?php
// API Index file for Hostinger
include_once 'config/cors.php';

// Simple API info endpoint
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Autriq API is running',
    'version' => '1.0.0',
    'endpoints' => [
        'health' => '/api/health.php',
        'auth' => '/api/auth/',
        'products' => '/api/products/',
        'categories' => '/api/categories/',
        'cart' => '/api/cart/',
        'orders' => '/api/orders/',
        'admin' => '/api/admin/'
    ]
]);
?>