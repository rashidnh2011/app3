<?php
require_once __DIR__ . '/_init.php';
$pdo = get_db_connection();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get action from query string or request body
$action = $_GET['action'] ?? '';
$data = [];

// Parse input based on content-type
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if ($method === 'POST' || $method === 'PUT') {
    if (str_contains($contentType, 'application/json')) {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
    } elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
        $data = $_POST;
    } else {
        // Fallback for other content types or missing content-type header
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $data = json_decode($input, true) ?? [];
        }
        if (empty($data) && !empty($_POST)) {
            $data = $_POST;
        }
    }
}

// Handle preflight OPTIONS request
if ($method === 'OPTIONS') {
    send_json(['success' => true]);
    exit;
}

// Route the request
switch ($method) {
    case 'POST':
        $action = $_GET['action'] ?? '';
        $data = json_decode(file_get_contents('php://input'), true);
        if ($action === 'register') {
            // Register user
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                send_json(['error' => 'Email already exists'], 409);
            }
            
            // Set default role to 'user' if not provided
            $role = isset($data['role']) && in_array(strtolower($data['role']), ['admin', 'editor', 'user']) 
                  ? strtolower($data['role']) 
                  : 'user';
                  
            $hashed = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                $data['name'], 
                $data['email'], 
                $hashed,
                $role
            ]);
            
            // Return user data with role
            $userId = $pdo->lastInsertId();
            $userData = [
                'id' => $userId,
                'email' => $data['email'],
                'name' => $data['name'] ?? '',
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            send_json([
                'success' => true, 
                'id' => $userId,
                'user' => $userData
            ]);
        } elseif ($action === 'login') {
            try {
                error_log('Login attempt for email: ' . ($data['email'] ?? 'not provided'));
                error_log('Request data: ' . print_r($data, true));
                error_log('Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
                
                if (empty($data['email']) || empty($data['password'])) {
                    error_log('Missing email or password');
                    send_json(['error' => 'Email and password are required'], 400);
                    return;
                }
                
                $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
                $stmt->execute([$data['email']]);
                $user = $stmt->fetch();
                
                error_log('User lookup result: ' . ($user ? 'User found' : 'User not found'));
                
                if ($user) {
                    error_log('Checking password for user: ' . $user['email']);
                    $passwordMatch = password_verify($data['password'], $user['password']);
                    error_log('Password match: ' . ($passwordMatch ? 'Yes' : 'No'));
                    
                    if (password_verify($data['password'], $user['password'])) {
                        error_log('Password verification successful for user: ' . $user['email']);
                        
                        // Remove password from user data before sending response
                        unset($user['password']);
                        
                        // Prepare user data for response
                        $userData = [
                            'id' => $user['id'],
                            'name' => $user['name'],
                            'email' => $user['email'],
                            'role' => $user['role']
                        ];
                        
                        error_log('Prepared user data: ' . print_r($userData, true));
                        
                        // Update last login timestamp
                        $updateStmt = $pdo->prepare('UPDATE users SET last_login = ? WHERE id = ?');
                        $updateResult = $updateStmt->execute([date('Y-m-d H:i:s'), $user['id']]);
                        error_log('Last login update ' . ($updateResult ? 'succeeded' : 'failed'));
                        
                        // Generate a secure token
                        $token = bin2hex(random_bytes(32));
                        error_log('Generated token: ' . $token);
                        
                        // Store token in session
                        $_SESSION['auth_token'] = $token;
                        
                        // Set token cookie
                        $cookieSet = setcookie('auth_token', $token, [
                            'expires' => time() + (86400 * 30),
                            'path' => '/',
                            'domain' => '',
                            'secure' => isset($_SERVER['HTTPS']),
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]);
                        error_log('Cookie set: ' . ($cookieSet ? 'yes' : 'no'));
                        
                        // Log the response being sent
                        $response = [
                            'success' => true,
                            'token' => $token,
                            'user' => $userData
                        ];
                        error_log('Sending successful login response: ' . json_encode($response));
                        
                        send_json($response);
                    } else {
                        error_log('Invalid password for user: ' . $user['email']);
                        send_json(['error' => 'Invalid email or password'], 401);
                    }
                } else {
                    error_log('No user found with email: ' . $data['email']);
                    send_json(['error' => 'Invalid email or password'], 401);
                }
            } catch (Exception $e) {
                error_log('Login error: ' . $e->getMessage());
                send_json(['error' => 'An error occurred during login'], 500);
            }
        } else {
            send_json(['error' => 'Invalid action'], 400);
        }
        break;
    default:
        send_json(['error' => 'Method not allowed'], 405);
}
