<?php
require_once '../db.php';
$input = json_decode(file_get_contents("php://input"), true);
$name = $input['name'] ?? null;
$display_order = $input['display_order'] ?? null;

if ($name === null || $name ==='') {
    echo json_encode(["success" => false, "message" => "Invalid name"]);
    exit;
}

if ($display_order === null || $display_order ==='') {
    echo json_encode(["success" => false, "message" => "Invalid display_order"]);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO categories (name, display_order) VALUES (?, ?)");
$stmt->execute([$name,$display_order]);
echo json_encode(["success" => true, "message" => "カテゴリーを追加しました"]);
