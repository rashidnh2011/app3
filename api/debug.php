<?php
// Debug file to check server configuration
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$debug_info = [
    'success' => true,
    'message' => 'Debug information',
    'server' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
        'http_host' => $_SERVER['HTTP_HOST'] ?? 'Unknown'
    ],
    'permissions' => [
        'current_directory' => getcwd(),
        'is_readable' => is_readable('.'),
        'is_writable' => is_writable('.'),
        'files_in_directory' => scandir('.')
    ],
    'php_extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'json' => extension_loaded('json'),
        'curl' => extension_loaded('curl')
    ]
];

echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>