<?php
require_once 'db.php';

$name = $_GET['name'] ?? null;
$hashed_password = $_GET['hashed_password'] ?? null;

if (!$name) {
    echo json_encode(["success" => false, "message" => "name is required"]);
    exit;
}

if (!$hashed_password) {
    echo json_encode(["success" => false, "message" => "hashed_password is required"]);
    exit;
}
$sql = "SELECT * FROM administrator WHERE name = BINARY ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$name]);
$user = $stmt->fetch();

if ($user)
{
    if ($user['hashed_password'] == $hashed_password){
    echo json_encode(["success" => true, "user" => $user]);
    }else{
        echo json_encode(["success" => false, "message" => "パスワードが違います".$hashed_password]);
    }
}
else
{
    echo json_encode(["success" => false, "message" => "アカウントが存在しません"]);
}