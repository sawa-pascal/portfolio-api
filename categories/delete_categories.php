<?php
require_once '../db.php';
$input = json_decode(file_get_contents("php://input"), true);
$id = $input['id'] ?? null;

if ($id === null || $id ==='') {
    echo json_encode(["success" => false, "message" => "Invalid id"]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
$stmt->execute([$id]);
echo json_encode(["success" => true, "message" => "カテゴリーを削除しました"]);
