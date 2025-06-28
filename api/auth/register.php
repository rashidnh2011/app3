<?php
include_once '../config/cors.php';
include_once '../config/database.php';
include_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->email) && !empty($data->password) && !empty($data->firstName) && !empty($data->lastName)) {
    $user->email = $data->email;
    
    // Check if email already exists
    if($user->emailExists()) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Email already exists"
        ]);
        exit;
    }

    $user->password = $data->password;
    $user->first_name = $data->firstName;
    $user->last_name = $data->lastName;
    $user->phone = $data->phone ?? null;
    $user->avatar = $data->avatar ?? null;
    $user->google_id = $data->googleId ?? null;

    if($user->create()) {
        $token = base64_encode(json_encode([
            'id' => $user->id,
            'email' => $user->email,
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ]));

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "User registered successfully",
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
                "emailVerified" => $user->email_verified
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to register user"
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Required fields: email, password, firstName, lastName"
    ]);
}
?>