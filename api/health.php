<?php
include_once 'config/cors.php';
include_once 'config/database.php';
include_once 'config/environment.php';

// Health check endpoint for monitoring
$database = new Database();
$db = $database->getConnection();

$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'environment' => ENVIRONMENT,
    'version' => '1.0.0',
    'checks' => [
        'database' => $database->isConnected() ? 'healthy' : 'unhealthy',
        'php_version' => PHP_VERSION,
        'memory_usage' => memory_get_usage(true),
        'disk_space' => disk_free_space('.'),
    ]
];

// Check if all systems are healthy
$allHealthy = $health['checks']['database'] === 'healthy';

http_response_code($allHealthy ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT);
?>