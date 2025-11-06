<?php
require_once 'db.php';

$email = $_GET['email'] ?? null;

if (!$email) {
    echo json_encode(["success" => false, "message" => "email is required"]);
    exit;
}

$sql = "SELECT hashed_password FROM users WHERE email = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);
$password = $stmt->fetch();

if ($password)
{
    echo json_encode(["success" => true, "password" => $password]);
}
else
{
    echo json_encode(["success" => false, "message" => "アカウントが存在しません"]);
}