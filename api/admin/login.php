<?php
include_once '../config/cors.php';
include_once '../config/database.php';
include_once '../models/Admin.php';

$database = new Database();
$db = $database->getConnection();
$admin = new Admin($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->email) && !empty($data->password)) {
    if($admin->login($data->email, $data->password)) {
        $token = base64_encode(json_encode([
            'id' => $admin->id,
            'email' => $admin->email,
            'role' => $admin->role,
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ]));

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Admin login successful",
            "token" => $token,
            "admin" => [
                "id" => $admin->id,
                "email" => $admin->email,
                "firstName" => $admin->first_name,
                "lastName" => $admin->last_name,
                "role" => $admin->role,
                "permissions" => $admin->permissions,
                "avatar" => $admin->avatar,
                "createdAt" => $admin->created_at
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid admin credentials"
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