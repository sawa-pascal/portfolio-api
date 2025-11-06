<?php
require_once 'db.php';

$email = $_GET['email'] ?? null;
$password = $_GET['password'] ?? null;

if (!$email) {
    echo json_encode(["success" => false, "message" => "email is required"]);
    exit;
}

if (!$password) {
    echo json_encode(["success" => false, "message" => "password is required"]);
    exit;
}
$sql = "SELECT * FROM users WHERE email = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user)
{
    if ($user['hashed_password'] == $password){
    echo json_encode(["success" => true, "user" => $user]);
    }else{
        echo json_encode(["success" => false, "message" => "パスワードが違います"]);
    }
}
else
{
    echo json_encode(["success" => false, "message" => "アカウントが存在しません"]);
}