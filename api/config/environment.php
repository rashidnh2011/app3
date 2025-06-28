<?php
// Environment configuration for production
define('ENVIRONMENT', isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') === false ? 'production' : 'development');

// Production settings
if (ENVIRONMENT === 'production') {
    // Disable error display
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    
    // Enable error logging
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
    
    // Security settings
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    
} else {
    // Development settings
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// JWT Secret (change this in production)
define('JWT_SECRET', ENVIRONMENT === 'production' ? 'your-super-secret-jwt-key-change-this' : 'dev-jwt-secret');

// File upload settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Rate limiting
define('RATE_LIMIT_REQUESTS', 100); // requests per hour
define('RATE_LIMIT_WINDOW', 3600); // 1 hour in seconds

// Cache settings
define('CACHE_ENABLED', ENVIRONMENT === 'production');
define('CACHE_TTL', 3600); // 1 hour
?>