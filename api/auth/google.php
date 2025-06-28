<?php
include_once '../config/cors.php';
include_once '../config/database.php';
include_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->googleId) && !empty($data->email)) {
    // Check if user exists with Google ID
    $existingUser = $user->findByGoogleId($data->googleId);
    
    if($existingUser) {
        // User exists, log them in
        $user->id = $existingUser['id'];
        $user->email = $existingUser['email'];
        $user->first_name = $existingUser['first_name'];
        $user->last_name = $existingUser['last_name'];
        $user->phone = $existingUser['phone'];
        $user->avatar = $existingUser['avatar'];
        $user->loyalty_points = $existingUser['loyalty_points'];
        $user->membership_tier = $existingUser['membership_tier'];
        $user->email_verified = $existingUser['email_verified'];
        $user->created_at = $existingUser['created_at'];
    } else {
        // Check if email exists
        $emailUser = $user->findByEmail($data->email);
        if($emailUser) {
            // Link Google account to existing user
            $query = "UPDATE users SET google_id = :google_id WHERE email = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":google_id", $data->googleId);
            $stmt->bindParam(":email", $data->email);
            $stmt->execute();
            
            $user->id = $emailUser['id'];
            $user->email = $emailUser['email'];
            $user->first_name = $emailUser['first_name'];
            $user->last_name = $emailUser['last_name'];
            $user->phone = $emailUser['phone'];
            $user->avatar = $emailUser['avatar'];
            $user->loyalty_points = $emailUser['loyalty_points'];
            $user->membership_tier = $emailUser['membership_tier'];
            $user->email_verified = 1; // Google accounts are verified
            $user->created_at = $emailUser['created_at'];
        } else {
            // Create new user
            $user->email = $data->email;
            $user->password = ''; // No password for Google users
            $user->first_name = $data->firstName ?? '';
            $user->last_name = $data->lastName ?? '';
            $user->phone = null;
            $user->avatar = $data->avatar ?? null;
            $user->google_id = $data->googleId;
            $user->email_verified = 1; // Google accounts are verified
            
            if(!$user->create()) {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Unable to create user account"
                ]);
                exit;
            }
        }
    }

    $token = base64_encode(json_encode([
        'id' => $user->id,
        'email' => $user->email,
        'exp' => time() + (24 * 60 * 60) // 24 hours
    ]));

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Google login successful",
        "token" => $token,
        "user" => [
            "id" => $user->id,
            "email" => $user->email,
            "firstName" => $user->first_name,
            "lastName" => $user->last_name,
            "phone" => $user->phone,
            "avatar" => $user->avatar,
            "loyaltyPoints" => $user->loyalty_points,
            "membershipTier" => $user->membership_tier,
            "emailVerified" => $user->email_verified,
            "createdAt" => $user->created_at
        ]
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Google ID and email are required"
    ]);
}
?>