<?php
require_once '../db.php';

// 入力取得
$input = json_decode(file_get_contents("php://input"), true);

$id = $input['id'] ?? null;
$newPassword = $input['newPassword'] ?? null;

if (!$id) {
    echo json_encode(["success" => false, "message" => "id is required"]);
    exit;
}

if (!$newPassword) {
    echo json_encode(["success" => false, "message" => "newPassword is required"]);
    exit;
}

$sql = "UPDATE users SET hashed_password = ? WHERE id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$newPassword, $id]);
$password = $stmt->fetch();

try {
    $sql = "UPDATE users SET hashed_password = ? WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$newPassword, $id]);

    echo json_encode((["success" => true, "message" => "パスワードを変更しました", "newPassword" => $newPassword]));
} catch (Exception $e) {
    echo json_encode((["success" => false, "message" => "パスワードの変更に失敗しました" . $e->getMessage()]));
}