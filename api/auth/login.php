<?php
include_once '../config/cors.php';
include_once '../config/database.php';
include_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->email) && !empty($data->password)) {
    if($user->login($data->email, $data->password)) {
        $token = base64_encode(json_encode([
            'id' => $user->id,
            'email' => $user->email,
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ]));

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Login successful",
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
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid email or password"
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Email and password are required"
    ]);
}
?>