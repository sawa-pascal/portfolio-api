<?php
require_once '../db.php';

$input = json_decode(file_get_contents("php://input"), true);
$id =$input['id']?? null;
$name = $input['name'] ?? null;
$display_order = $input['display_order'] ?? null;

if ($id === null || $id ==='') {
    echo json_encode(["success" => false, "message" => "Invalid id"]);
    exit;
}

if ($name === null || $name ==='') {
    echo json_encode(["success" => false, "message" => "Invalid name"]);
    exit;
}

if ($display_order === null || $display_order ==='') {
    echo json_encode(["success" => false, "message" => "Invalid display_order"]);
    exit;
}

$stmt = $pdo->prepare("UPDATE categories SET name=  ?, display_order = ? WHERE id = ?");
$stmt->execute([$name,$display_order,$id]);
echo json_encode(["success" => true, "message" => "カテゴリーを更新しました"]);
