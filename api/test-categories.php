<?php
// Test file to check categories specifically
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test database connection
    $testQuery = "SELECT 1 as test";
    $testStmt = $db->prepare($testQuery);
    $testStmt->execute();
    $testResult = $testStmt->fetch();
    
    // Check if categories table exists
    $tableQuery = "SHOW TABLES LIKE 'categories'";
    $tableStmt = $db->prepare($tableQuery);
    $tableStmt->execute();
    $tableExists = $tableStmt->rowCount() > 0;
    
    // Count categories
    $countQuery = "SELECT COUNT(*) as count FROM categories";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $countResult = $countStmt->fetch();
    
    // Get sample categories
    $sampleQuery = "SELECT * FROM categories LIMIT 5";
    $sampleStmt = $db->prepare($sampleQuery);
    $sampleStmt->execute();
    $sampleCategories = $sampleStmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'message' => 'Categories test successful',
        'database_connection' => $testResult ? 'OK' : 'Failed',
        'categories_table_exists' => $tableExists,
        'categories_count' => $countResult['count'],
        'sample_categories' => $sampleCategories,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Test failed',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>